<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;

use function Symfony\Component\Marshaller\marshal;

use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

use function Symfony\Component\Marshaller\unmarshal;

final class MarshalUnmarshalTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/marshal', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider marshalUnmarshalDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testMarshalUnmarshal(mixed $data, string $type, array $context = []): void
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        marshal($data, $resource, 'json', ['type' => $type] + $context);
        rewind($resource);

        $this->assertEquals($data, unmarshal($resource, $type, 'json', $context));
    }

    /**
     * @return iterable<array{0: mixed, 1: string, 2: array<string, mixed>}>
     */
    public function marshalUnmarshalDataProvider(): iterable
    {
        yield [1, 'int'];
        yield [null, '?int'];
        yield ['foo', 'string'];
        yield [[1, 2, null], 'array<int, ?int>'];
        yield [['foo' => 1, 'bar' => 2, 'baz' => null], 'array<string, ?int>'];

        $dummy = new ClassicDummy();
        $dummy->id = 100;
        $dummy->name = 'Dummy';

        yield [$dummy, ClassicDummy::class];

        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 200;
        $dummy->name = '200';

        yield [$dummy, DummyWithFormatterAttributes::class, ['hooks' => [
            'marshal' => [
                sprintf('%s::$name', DummyWithFormatterAttributes::class) => fn (\ReflectionProperty $p, string $accessor) => [
                    'name' => '@name',
                    'accessor' => sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $accessor),
                ],
            ],
            'unmarshal' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];
    }

    /**
     * @dataProvider unmarshalMarshalDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testUnmarshalMarshal(string $content, string $type, array $context = []): void
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        $data = unmarshal($resource, $type, 'json', $context);

        /** @var resource $resource */
        $newResource = fopen('php://memory', 'w+');

        marshal($data, $newResource, 'json', ['type' => $type] + $context);
        rewind($newResource);

        $this->assertEquals($content, stream_get_contents($newResource));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public function unmarshalMarshalDataProvider(): iterable
    {
        yield ['1', 'int'];
        yield ['null', '?int'];
        yield ['"foo"', 'string'];
        yield ['[1,2,null]', 'array<int, ?int>'];
        yield ['{"foo":1,"bar":2,"baz":null}', 'array<string, ?int>'];
        yield ['{"id":100,"name":"Dummy"}', ClassicDummy::class];
        yield ['{"id":200,"@name":100}', DummyWithFormatterAttributes::class, ['hooks' => [
            'marshal' => [
                sprintf('%s::$name', DummyWithFormatterAttributes::class) => fn (\ReflectionProperty $p, string $accessor) => [
                    'name' => '@name',
                    'accessor' => sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $accessor),
                ],
            ],
            'unmarshal' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];
    }
}
