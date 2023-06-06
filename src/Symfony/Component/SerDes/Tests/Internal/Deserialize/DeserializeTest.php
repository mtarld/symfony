<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Deserialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\PartialDeserializationException;
use Symfony\Component\SerDes\Exception\UnexpectedValueException;
use Symfony\Component\SerDes\Exception\UnsupportedFormatException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\TypeFactory;

use function Symfony\Component\SerDes\deserialize;

class DeserializeTest extends TestCase
{
    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeScalar(callable $deserialize)
    {
        $this->assertEquals(1, $deserialize('1', 'int'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeUnionType(callable $deserialize)
    {
        $this->assertSame([1, 2, 3], $deserialize('[1, "2", "3"]', 'array<int, int|string>', context: ['union_selector' => ['int|string' => 'int']]));
        $this->assertEquals(1, $deserialize('1', 'int', context: ['lazy_reading' => true]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess type to use for "int|string", you may specify a type in "$context[\'union_selector\'][\'int|string\']".');

        $this->assertSame([1, 2, 3], $deserialize('[1, "2", "3"]', 'array<int, int|string>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeRawArray(callable $deserialize)
    {
        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $deserialize('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'array'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeArray(callable $deserialize)
    {
        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $deserialize('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'array<int, array<string, int>>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeRawIterable(callable $deserialize)
    {
        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $deserialize('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'iterable'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeIterable(callable $deserialize)
    {
        $value = $deserialize('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'iterable<int, iterable<string, int>>');

        $this->assertInstanceOf(\Generator::class, $value);

        $result = [];
        foreach ($value as $v) {
            $this->assertInstanceOf(\Generator::class, $v);
            $result[] = iterator_to_array($v);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $result);
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeRawObject(callable $deserialize)
    {
        $value = $deserialize('{"id": 123, "name": "thename"}', 'object');

        $expectedObject = new \stdClass();
        $expectedObject->id = 123;
        $expectedObject->name = 'thename';

        $this->assertEquals($expectedObject, $value);
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeEnum(callable $deserialize)
    {
        $this->assertEquals(DummyBackedEnum::ONE, $deserialize('1', DummyBackedEnum::class));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeObject(callable $deserialize)
    {
        $value = $deserialize('{"id": 123, "name": "thename"}', ClassicDummy::class);

        $expectedObject = new ClassicDummy();
        $expectedObject->id = 123;
        $expectedObject->name = 'thename';

        $this->assertEquals($expectedObject, $value);

        $value = $deserialize('{"@id": 123, "name": "thename"}', ClassicDummy::class, [
            'hooks' => [
                'deserialize' => [
                    sprintf('%s[@id]', ClassicDummy::class) => static function (\ReflectionClass $class, string $key, callable $value, array $context): array {
                        return [
                            'name' => 'id',
                        ];
                    },
                    sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $class, string $key, callable $value, array $context): array {
                        return [
                            'value_provider' => fn () => 'HOOK_VALUE',
                        ];
                    },
                ],
            ],
        ]);
        $expectedObject->name = 'HOOK_VALUE';

        $this->assertEquals($expectedObject, $value);
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeObjectSkipWithNullValueProvider(callable $deserialize)
    {
        $expectedObject = new ClassicDummy();
        $expectedObject->id = 123;
        $expectedObject->name = 'dummy';

        $value = $deserialize('{"id": 123, "name": "thename"}', ClassicDummy::class, [
            'hooks' => [
                'deserialize' => [
                    sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $class, string $key, callable $value, array $context): array {
                        return [
                            'value_provider' => null,
                        ];
                    },
                ],
            ],
        ]);

        $this->assertEquals($expectedObject, $value);
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeMixed(callable $deserialize)
    {
        $this->assertSame(['foo' => true], $deserialize('{"foo": true}', 'mixed'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowWhenNotCollecting(callable $deserialize)
    {
        $this->expectException(UnexpectedValueException::class);

        $deserialize('{"name": {"foo": "bar"}}', ClassicDummy::class);
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowPartialWhenCollecting(callable $deserialize)
    {
        try {
            $deserialize('[{"name": "ok"}, {"name": "ko"}, {"name": "ok"}, {"name": "ko"}]', sprintf('array<int, %s>', ClassicDummy::class), context: [
                'collect_errors' => true,
                'hooks' => [
                    'deserialize' => [
                        sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $class, string $key, callable $value, array $context): array {
                            $ok = $value('string', $context);

                            return [
                                'value_provider' => fn () => 'ok' === $ok ? 'ok' : new \DateTimeImmutable(),
                            ];
                        },
                    ],
                ],
            ]);

            $this->fail(sprintf('"%s" has not been thrown.', PartialDeserializationException::class));
        } catch (PartialDeserializationException $e) {
            $okDummy = new ClassicDummy();
            $okDummy->name = 'ok';

            $koDummy = new ClassicDummy();

            $this->assertEquals([$okDummy, $koDummy, $okDummy, $koDummy], $e->deserialized);

            $this->assertCount(2, $e->errors);
            $this->assertContainsOnlyInstancesOf(UnexpectedValueException::class, $e->errors);
        }
    }

    public function testNotThrowWhenNotValidateInvalidStream()
    {
        self::deserialize('{[]}', ClassicDummy::class, ['lazy_reading' => true]);

        $this->addToAssertionCount(1);
    }

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        deserialize(fopen('php://memory', 'w+'), TypeFactory::createFromString('int'), 'unknown', []);
    }

    /**
     * @return iterable<array{0: callable(mixed, string, array<string, mixed>): mixed, 1: bool}>
     */
    public static function deserializeDataProvider(): iterable
    {
        yield [fn (string $content, string $type, array $context = []): mixed => self::deserialize($content, $type, $context), false];
        yield [fn (string $content, string $type, array $context = []): mixed => self::deserialize($content, $type, ['lazy_reading' => true] + $context), true];
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function deserialize(string $content, string $type, array $context): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        return deserialize($resource, TypeFactory::createFromString($type), 'json', $context);
    }
}
