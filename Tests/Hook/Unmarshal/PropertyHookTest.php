<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Hook\Unmarshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
null
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class PropertyHookTest extends TestCase
{
    public function testUpdateObjectProperties(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn('int');

        $object = new class() {
            public int $foo = 0;
        };

        $valueType = null;
        $value = static function (string $type, array $context) use (&$valueType): int {
            $valueType = $type;

            return 1000;
        };

        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'foo', $value, []);

        $this->assertSame(1000, $object->foo);
        $this->assertSame('int', $valueType);
    }

    public function testRetrieveActualPropertyName(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $object = new class() {
            public int $foo = 0;
        };

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'property_name' => [
                        $object::class => ['fooAlias' => 'foo'],
                    ],
                ],
            ],
        ];

        $value = fn () => 1000;

        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'fooAlias', $value, $context);

        $this->assertSame(1000, $object->foo);
    }

    public function testFormatValue(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn('string');

        $object = new class() {
            public string $foo;
            public \DateTimeInterface $bar;

            public static function castToDateTime(string $value, array $context): \DateTimeInterface
            {
                return new \DateTimeImmutable($value);
            }
        };

        $valueType = null;
        $value = static function (string $type, array $context) use (&$valueType): string {
            $valueType = $type;

            return '2023-01-01';
        };

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => [
                        sprintf('%s::$bar', $object::class) => $object::castToDateTime(...),
                    ],
                ],
            ],
        ];

        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'foo', $value, $context);
        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'bar', $value, $context);

        $this->assertSame('2023-01-01', $object->foo);
        $this->assertEquals(new \DateTimeImmutable('2023-01-01'), $object->bar);
        $this->assertSame('string', $valueType);
    }

    public function testFormatValueOnUpdatedName(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $object = new class() {
            public \DateTimeInterface $createdAt;

            public static function castToDateTime(string $value, array $context): \DateTimeInterface
            {
                return new \DateTimeImmutable($value);
            }
        };

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'property_name' => [
                        $object::class => ['created_at' => 'createdAt'],
                    ],
                    'property_formatter' => [
                        sprintf('%s::$createdAt', $object::class) => $object::castToDateTime(...),
                    ],
                ],
            ],
        ];

        $value = fn () => '2023-01-01';

        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'created_at', $value, $context);

        $this->assertEquals(new \DateTimeImmutable('2023-01-01'), $object->createdAt);
    }

    /**
     * @dataProvider throwWhenWrongFormatterDataProvider
     */
    public function testThrowWhenWrongFormatter(string $exceptionMessage, callable $formatter): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => [
                        sprintf('%s::$id', ClassicDummy::class) => $formatter,
                    ],
                ],
            ],
        ];

        $object = new ClassicDummy();
        $value = fn () => 1000;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new PropertyHook($typeExtractor))(new \ReflectionClass($object), $object, 'id', $value, $context);
    }

    /**
     * @return iterable<array{0: string, 1: callable}>
     */
    public function throwWhenWrongFormatterDataProvider(): iterable
    {
        yield [
            sprintf('Property formatter "%s::$id" must be a static method.', ClassicDummy::class),
            (new DummyWithMethods())->nonStatic(...),
        ];

        yield [
            sprintf('Property formatter "%s::$id" must have at least one argument.', ClassicDummy::class),
            DummyWithMethods::noArgument(...),
        ];

        yield [
            sprintf('Return type of property formatter "%s::$id" must not be "void" nor "never".', ClassicDummy::class),
            DummyWithMethods::void(...),
        ];

        yield [
            sprintf('Second argument of property formatter "%s::$id" must be an array.', ClassicDummy::class),
            DummyWithMethods::invalidContextType(...),
        ];
    }

    public function testConvertGenericTypes(): void
    {
        $foo = new class() {
            public $foo;
        };

        $bar = new class() {
            public $bar;
        };

        $baz = new class() {
            public $baz;

            public static function baz(int $_): int
            {
                return 0;
            }
        };

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn('T');
        $typeExtractor->method('extractFromFunctionParameter')->willReturn('U');

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'generic_parameter_types' => [
                        $foo::class => ['T' => 'string'],
                        $baz::class => ['U' => 'int'],
                    ],
                    'property_formatter' => [
                        sprintf('%s::$baz', $baz::class) => $baz::baz(...),
                    ],
                ],
            ],
        ];

        $hook = new PropertyHook($typeExtractor);

        $type = null;

        $value = static function (string $t) use (&$type): int {
            $type = $t;

            return 1;
        };

        $hook(new \ReflectionClass($foo), $foo, 'foo', $value, $context);
        $this->assertSame('string', $type);

        $hook(new \ReflectionClass($bar), $bar, 'bar', $value, $context);
        $this->assertSame('T', $type);

        $hook(new \ReflectionClass($baz), $baz, 'baz', $value, $context);
        $this->assertSame('int', $type);
    }

    public function testDoNotConvertGenericTypesWhenFormatterDoesNotBelongToCurrentClass(): void
    {
        $foo = new class() {
            public $foo;
        };

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn('T');

        $context = [
            '_symfony' => [
                'unmarshal' => [
                    'generic_parameter_types' => [
                        $foo::class => ['T' => 'string'],
                    ],
                    'property_formatter' => [
                        sprintf('%s::$foo', $foo::class) => DummyWithMethods::doubleAndCastToString(...),
                    ],
                ],
            ],
        ];

        $hook = new PropertyHook($typeExtractor);

        $type = null;

        $value = static function (string $t) use (&$type): int {
            $type = $t;

            return 1;
        };

        $hook(new \ReflectionClass($foo), $foo, 'foo', $value, $context);
        $this->assertSame('T', $type);
    }
}
