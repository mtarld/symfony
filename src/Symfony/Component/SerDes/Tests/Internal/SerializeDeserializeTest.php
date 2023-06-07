<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\TypeFactory;

use function Symfony\Component\SerDes\deserialize;
use function Symfony\Component\SerDes\serialize;

class SerializeDeserializeTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_ser_des', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider serializeDeserializeDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testSerializeDeserialize(mixed $data, string $type, string $format, array $context = [])
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        serialize($data, $resource, $format, ['type' => $type] + $context);
        rewind($resource);

        $this->assertEquals($data, deserialize($resource, TypeFactory::createFromString($type), $format, $context));
    }

    /**
     * @return iterable<array{0: mixed, 1: string, 2: string, 3: array<string, mixed>}>
     */
    public static function serializeDeserializeDataProvider(): iterable
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 200;
        $dummy->name = '200';

        yield [1, 'int', 'json'];
        yield [null, '?int', 'json'];
        yield ['foo', 'string', 'json'];
        yield [[1, 2, null], 'array<int, ?int>', 'json'];
        yield [['foo' => 1, 'bar' => 2, 'baz' => null], 'array<string, ?int>', 'json'];
        yield [DummyBackedEnum::ONE, DummyBackedEnum::class, 'json'];
        yield [new ClassicDummy(), ClassicDummy::class, 'json'];
        yield [$dummy, DummyWithFormatterAttributes::class, 'json', ['hooks' => [
            'serialize' => [
                DummyWithFormatterAttributes::class => function (string $type, string $accessor, array $properties, array $context): array {
                    $properties['name']['name'] = '@name';
                    $properties['name']['accessor'] = sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $properties['name']['accessor']);

                    return ['properties' => $properties];
                },
            ],
            'deserialize' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];

        yield [[1], 'array<int, int>', 'csv'];
        yield [[1, 2, null], 'array<int, ?int>', 'csv'];
        yield [['foo'], 'array<int, string>', 'csv'];
        yield [[['foo' => 1, 'bar' => null], ['foo' => null, 'bar' => 2]], 'array<int, array<string, ?int>>', 'csv'];
        yield [[DummyBackedEnum::ONE], sprintf('array<int, %s>', DummyBackedEnum::class), 'csv'];
        yield [[new ClassicDummy()], sprintf('array<int, %s>', ClassicDummy::class), 'csv'];
        yield [[$dummy], sprintf('array<int, %s>', DummyWithFormatterAttributes::class), 'csv', ['hooks' => [
            'serialize' => [
                DummyWithFormatterAttributes::class => function (string $type, string $accessor, array $properties, array $context): array {
                    $properties['name']['name'] = '@name';
                    $properties['name']['accessor'] = sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $properties['name']['accessor']);

                    return ['properties' => $properties];
                },
            ],
            'deserialize' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];
    }

    /**
     * @dataProvider deserializeSerializeDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testDeserializeSerialize(string $content, string $type, string $format, array $context = [])
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        $data = deserialize($resource, TypeFactory::createFromString($type), $format, $context);

        /** @var resource $newResource */
        $newResource = fopen('php://memory', 'w+');

        serialize($data, $newResource, $format, ['type' => TypeFactory::createFromString($type)] + $context);
        rewind($newResource);

        $this->assertEquals($content, stream_get_contents($newResource));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string, 3: array<string, mixed>}>
     */
    public static function deserializeSerializeDataProvider(): iterable
    {
        yield ['1', 'int', 'json'];
        yield ['null', '?int', 'json'];
        yield ['"foo"', 'string', 'json'];
        yield ['[1,2,null]', 'array<int, ?int>', 'json'];
        yield ['{"foo":1,"bar":2,"baz":null}', 'array<string, ?int>', 'json'];
        yield ['{"id":100,"name":"Dummy"}', ClassicDummy::class, 'json'];
        yield ['{"id":200,"@name":100}', DummyWithFormatterAttributes::class, 'json', ['hooks' => [
            'serialize' => [
                DummyWithFormatterAttributes::class => function (string $type, string $accessor, array $properties, array $context): array {
                    $properties['name']['name'] = '@name';
                    $properties['name']['accessor'] = sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $properties['name']['accessor']);

                    return ['properties' => $properties];
                },
            ],
            'deserialize' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];

        yield ["0\n1\n", 'array<int, int>', 'csv'];
        yield ["0\n1\n2\n", 'array<int, ?int>', 'csv'];
        yield ["0\nfoo\n", 'array<int, string>', 'csv'];
        yield ["foo,bar\n1,\n,2\n", 'array<int, array<string, ?int>>', 'csv'];
        yield ["id,name\n100,Dummy\n", sprintf('array<int, %s>', ClassicDummy::class), 'csv'];
        yield ["id,@name\n200,100\n", sprintf('array<int, %s>', DummyWithFormatterAttributes::class), 'csv', ['hooks' => [
            'serialize' => [
                DummyWithFormatterAttributes::class => function (string $type, string $accessor, array $properties, array $context): array {
                    $properties['name']['name'] = '@name';
                    $properties['name']['accessor'] = sprintf('%s::divideAndCastToInt(%s, $context)', DummyWithFormatterAttributes::class, $properties['name']['accessor']);

                    return ['properties' => $properties];
                },
            ],
            'deserialize' => [
                sprintf('%s[@name]', DummyWithFormatterAttributes::class) => fn (\ReflectionClass $class, string $key, callable $value, array $context) => [
                    'name' => 'name',
                    'value_provider' => fn () => DummyWithFormatterAttributes::doubleAndCastToString($value('int', $context)),
                ],
            ],
        ]]];
    }
}
