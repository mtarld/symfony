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
use Symfony\Component\SerDes\Exception\InvalidConstructorArgumentException;
use Symfony\Component\SerDes\Exception\InvalidResourceException;
use Symfony\Component\SerDes\Exception\PartialDeserializationException;
use Symfony\Component\SerDes\Exception\UnexpectedTypeException;
use Symfony\Component\SerDes\Exception\UnsupportedFormatException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithConstructorWithRequiredValues;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;

use function Symfony\Component\SerDes\deserialize;

class DeserializeTest extends TestCase
{
    public function testDeserializeScalar()
    {
        $this->assertEquals(1, $this->deserializeString('1', 'int'));
    }

    public function testDeserializeUnionType()
    {
        $this->assertSame([1, 2, 3], $this->deserializeString('[1, "2", "3"]', 'array<int, int|string>', context: ['union_selector' => ['int|string' => 'int']]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess type to use for "int|string", you may specify a type in "$context[\'union_selector\'][\'int|string\']".');

        $this->assertSame([1, 2, 3], $this->deserializeString('[1, "2", "3"]', 'array<int, int|string>'));
    }

    public function testDeserializeIterable()
    {
        $value = $this->deserializeString('[{"foo": 1, "bar": 2}, {"baz": 3}]', 'iterable<int, iterable<string, int>>');

        $this->assertInstanceOf(\Generator::class, $value);

        $result = [];
        foreach ($value as $v) {
            $this->assertInstanceOf(\Generator::class, $v);
            $result[] = iterator_to_array($v);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['baz' => 3]], $result);
    }

    public function testDeserializeEnum()
    {
        $this->assertEquals(DummyBackedEnum::ONE, $this->deserializeString('1', DummyBackedEnum::class));
    }

    public function testDeserializeObject()
    {
        $value = $this->deserializeString('{"id": 123, "name": "thename"}', ClassicDummy::class);

        $expectedObject = new ClassicDummy();
        $expectedObject->id = 123;
        $expectedObject->name = 'thename';

        $this->assertEquals($expectedObject, $value);

        $value = $this->deserializeString('{"@id": 123, "name": "thename"}', ClassicDummy::class, 'json', [
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

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        deserialize(fopen('php://memory', 'w+'), 'int', 'unknown', []);
    }

    public function testThrowWhenNotCollecting()
    {
        $this->expectException(InvalidConstructorArgumentException::class);

        $this->deserializeString('{}', DummyWithConstructorWithRequiredValues::class);
    }

    public function testThrowPartialWhenCollecting()
    {
        try {
            $this->deserializeString('[{"name": "ok"}, {"name": "ko"}, {"name": "ok"}, {"name": "ko"}]', sprintf('array<int, %s>', ClassicDummy::class), context: [
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
            $this->assertContainsOnlyInstancesOf(UnexpectedTypeException::class, $e->errors);
        }
    }

    public function testThrowWhenValidateInvalidStream()
    {
        $this->expectException(InvalidResourceException::class);

        $this->deserializeString('{[]}', ClassicDummy::class, context: ['lazy_reading' => true, 'validate_stream' => true]);
    }

    public function testNotThrowWhenNotValidateInvalidStream()
    {
        $this->deserializeString('{[]}', ClassicDummy::class, context: ['lazy_reading' => true]);
        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deserializeString(string $string, string $type, string $format = 'json', array $context = []): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $string);
        rewind($resource);

        return deserialize($resource, $type, $format, $context);
    }
}
