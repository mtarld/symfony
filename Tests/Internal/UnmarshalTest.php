<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithConstructorWithRequiredValues;

use function Symfony\Component\Marshaller\unmarshal;

final class UnmarshalTest extends TestCase
{
    /**
     * @dataProvider unmarshalDataProvider
     */
    public function testUnmarshal(string $json, string $type): void
    {
        $this->assertSame(json_decode($json, associative: true), $this->unmarshalString($json, $type));
    }

    /**
     * @return iterable<array{0: mixed, 1: string?}>
     */
    public function unmarshalDataProvider(): iterable
    {
        yield ['1', 'int'];
        yield ['"foo"', 'string'];
        yield ['-1e100', 'float'];
        yield ['true', 'bool'];
        yield ['null', '?bool'];
        yield ['[[1, 2], [3, null], null]', 'array<int, ?array<int, ?int>>'];
        yield ['{"foo": {"bar": 1, "baz": null}, "foo2": null}', 'array<string, ?array<string, ?int>>'];
    }

    public function testUnmarshalUnionType(): void
    {
        $this->assertSame([1, 2, 3], $this->unmarshalString('[1, "2", "3"]', 'array<int, int|string>', context: ['union_selector' => ['int|string' => 'int']]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess type to use for "int|string", you may specify a type in "$context[\'union_selector\'][\'int|string\']".');

        $this->assertSame([1, 2, 3], $this->unmarshalString('[1, "2", "3"]', 'array<int, int|string>'));
    }

    public function testUnmarshalIterable(): void
    {
        $value = $this->unmarshalString('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'iterable<int, iterable<string, int>>');

        $this->assertInstanceOf(\Generator::class, $value);

        $result = [];
        foreach ($value as $v) {
            $this->assertInstanceOf(\Generator::class, $v);
            $result[] = iterator_to_array($v);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $result);
    }

    public function testUnmarshalObject(): void
    {
        $value = $this->unmarshalString('{"id": 123, "name": "thename"}', ClassicDummy::class);

        $expectedObject = new ClassicDummy();
        $expectedObject->id = 123;
        $expectedObject->name = 'thename';

        $this->assertEquals($expectedObject, $value);

        $value = $this->unmarshalString('{"@id": 123, "name": "thename"}', ClassicDummy::class, context: [
            'hooks' => [
                sprintf('%s[@id]', ClassicDummy::class) => static function (\ReflectionClass $reflection, object $object, string $key, callable $value, array $context): void {
                    $object->id = $value('int', $context);
                },
                sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $reflection, object $object, string $key, callable $value, array $context): void {
                    $object->name = 'HOOK_VALUE';
                },
            ],
        ]);
        $expectedObject->name = 'HOOK_VALUE';

        $this->assertEquals($expectedObject, $value);
    }

    public function testUnmarshalWithJsonDecodeFlags(): void
    {
        $this->assertSame('1.2345678901235E+29', $this->unmarshalString('123456789012345678901234567890', 'string'));
        $this->assertSame('123456789012345678901234567890', $this->unmarshalString('123456789012345678901234567890', 'string', context: ['json_decode_flags' => \JSON_BIGINT_AS_STRING]));
    }

    public function testThrowOnUnknownFormat(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        unmarshal(fopen('php://memory', 'w+'), 'int', 'unknown', []);
    }

    public function testThrowWhenNotCollecting(): void
    {
        $this->expectException(InvalidConstructorArgumentException::class);

        $this->unmarshalString('{}', DummyWithConstructorWithRequiredValues::class);
    }

    public function testThrowPartialWhenCollecting(): void
    {
        try {
            $this->unmarshalString('[{"name": "ok"}, {"name": "ko"}, {"name": "ok"}, {"name": "ko"}]', sprintf('array<int, %s>', ClassicDummy::class), context: [
                'collect_errors' => true,
                'hooks' => [
                    sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $reflection, object $object, string $key, callable $value, array $context): void {
                        $name = $value('string', $context);
                        $object->name = 'ok' === $name ? 'ok' : new \stdClass();
                    },
                ],
            ]);

            $this->fail(sprintf('"%s" has not been thrown.', PartialUnmarshalException::class));
        } catch (PartialUnmarshalException $e) {
            $okDummy = new ClassicDummy();
            $okDummy->name = 'ok';

            $koDummy = new ClassicDummy();

            $this->assertEquals([$okDummy, $koDummy, $okDummy, $koDummy], $e->unmarshalled);

            $this->assertCount(2, $e->errors);
            $this->assertContainsOnlyInstancesOf(UnexpectedTypeException::class, $e->errors);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function unmarshalString(string $string, string $type, string $format = 'json', array $context = []): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $string);
        rewind($resource);

        return unmarshal($resource, $type, $format, $context);
    }
}