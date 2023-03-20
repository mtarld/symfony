<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Unmarshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithConstructorWithRequiredValues;

use function Symfony\Component\Marshaller\unmarshal;

class UnmarshalTest extends TestCase
{
    public function testUnmarshalUnionType()
    {
        $this->assertSame([1, 2, 3], $this->unmarshalString('[1, "2", "3"]', 'array<int, int|string>', context: ['union_selector' => ['int|string' => 'int']]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess type to use for "int|string", you may specify a type in "$context[\'union_selector\'][\'int|string\']".');

        $this->assertSame([1, 2, 3], $this->unmarshalString('[1, "2", "3"]', 'array<int, int|string>'));
    }

    public function testUnmarshalIterable()
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

    public function testUnmarshalObject()
    {
        $value = $this->unmarshalString('{"id": 123, "name": "thename"}', ClassicDummy::class);

        $expectedObject = new ClassicDummy();
        $expectedObject->id = 123;
        $expectedObject->name = 'thename';

        $this->assertEquals($expectedObject, $value);

        $value = $this->unmarshalString('{"@id": 123, "name": "thename"}', ClassicDummy::class, 'json', [
            'hooks' => [
                'unmarshal' => [
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

        unmarshal(fopen('php://memory', 'w+'), 'int', 'unknown', []);
    }

    public function testThrowWhenNotCollecting()
    {
        $this->expectException(InvalidConstructorArgumentException::class);

        $this->unmarshalString('{}', DummyWithConstructorWithRequiredValues::class);
    }

    public function testThrowPartialWhenCollecting()
    {
        try {
            $this->unmarshalString('[{"name": "ok"}, {"name": "ko"}, {"name": "ok"}, {"name": "ko"}]', sprintf('array<int, %s>', ClassicDummy::class), context: [
                'collect_errors' => true,
                'hooks' => [
                    'unmarshal' => [
                        sprintf('%s[name]', ClassicDummy::class) => static function (\ReflectionClass $class, string $key, callable $value, array $context): array {
                            $ok = $value('string', $context);

                            return [
                                'value_provider' => fn () => 'ok' === $ok ? 'ok' : new \DateTimeImmutable(),
                            ];
                        },
                    ],
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

    public function testThrowWhenValidateInvalidStream()
    {
        $this->expectException(InvalidResourceException::class);

        $this->unmarshalString('{[]}', ClassicDummy::class, context: ['validate_stream' => true]);
    }

    public function testNotThrowWhenNotValidateInvalidStream()
    {
        $this->unmarshalString('{[]}', ClassicDummy::class);
        $this->addToAssertionCount(1);
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
