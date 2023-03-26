<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Hook\Deserialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Hook\Deserialize\PropertyHook;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\SerDes\Type\PhpstanTypeExtractor;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;

class PropertyHookTest extends TestCase
{
    public function testRetrievePropertyName()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            '_symfony' => [
                'deserialize' => [
                    'property_name' => [
                        ClassicDummy::class => [
                            '@id' => 'id',
                        ],
                    ],
                ],
            ],
        ];

        $result = (new PropertyHook($typeExtractor))(new \ReflectionClass(ClassicDummy::class), '@id', fn () => null, $context);

        $this->assertSame('id', $result['name']);
    }

    public function testSkipOnInvalidProperty()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $result = (new PropertyHook($typeExtractor))(new \ReflectionClass(ClassicDummy::class), 'invalid', fn () => null, []);

        $this->assertSame([], $result);
    }

    public function testRetrievePropertyType()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $type = null;
        $valueProvider = static function (string $valueType, array $context) use (&$type) {
            $type = $valueType;
        };

        (new PropertyHook($typeExtractor))(new \ReflectionClass(ClassicDummy::class), 'name', $valueProvider, [])['value_provider']();

        $this->assertSame('string', $type);
    }

    public function testRetrievePropertyTypeWithGenerics()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithGenerics::class => ['T' => ClassicDummy::class],
                ],
            ],
        ];

        $type = null;
        $valueProvider = static function (string $valueType, array $context) use (&$type) {
            $type = $valueType;
        };

        (new PropertyHook($typeExtractor))(new \ReflectionClass(DummyWithGenerics::class), 'dummies', $valueProvider, $context)['value_provider']();

        $this->assertSame(sprintf('array<int, %s>', ClassicDummy::class), $type);
    }

    public function testRetrievePropertyTypeWithFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $type = null;
        $valueProvider = static function (string $valueType, array $context) use (&$type): int {
            $type = $valueType;

            return 123;
        };

        $context = [
            '_symfony' => [
                'deserialize' => [
                    'property_formatter' => [
                        ClassicDummy::class => [
                            'name' => fn (int $v, array $c): string => (string) $v,
                        ],
                    ],
                ],
            ],
        ];

        (new PropertyHook($typeExtractor))(new \ReflectionClass(ClassicDummy::class), 'name', $valueProvider, $context)['value_provider']();

        $this->assertSame('int', $type);
    }

    public function testRetrievePropertyTypeWithFormatterAndGenerics()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn('T');

        $type = null;
        $valueProvider = static function (string $valueType, array $context) use (&$type): int {
            $type = $valueType;

            return 123;
        };

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithFormatterAttributes::class => ['T' => 'string'],
                ],
                'deserialize' => [
                    'property_formatter' => [
                        DummyWithFormatterAttributes::class => [
                            'name' => DummyWithFormatterAttributes::doubleAndCastToString(...),
                        ],
                    ],
                ],
            ],
        ];

        (new PropertyHook($typeExtractor))(new \ReflectionClass(DummyWithFormatterAttributes::class), 'name', $valueProvider, $context)['value_provider']();

        $this->assertSame('string', $type);
    }

    public function testDoNotReplaceGenericTypesWhenFormatterDoesNotBelongToCurrentClass()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn('T');

        $type = null;
        $valueProvider = static function (string $valueType, array $context) use (&$type): int {
            $type = $valueType;

            return 123;
        };

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithQuotes::class => ['T' => 'string'],
                ],
                'deserialize' => [
                    'property_formatter' => [
                        DummyWithQuotes::class => [
                            'name' => fn (mixed $v, array $c): string => (string) $v,
                        ],
                    ],
                ],
            ],
        ];

        (new PropertyHook($typeExtractor))(new \ReflectionClass(DummyWithQuotes::class), 'name', $valueProvider, $context)['value_provider']();

        $this->assertSame('T', $type);
    }

    public function testFormatValue()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            '_symfony' => [
                'deserialize' => [
                    'property_formatter' => [
                        ClassicDummy::class => [
                            'name' => fn (string $v, array $c): string => strtoupper($v),
                        ],
                    ],
                ],
            ],
        ];

        $result = (new PropertyHook($typeExtractor))(new \ReflectionClass(ClassicDummy::class), 'name', fn () => 'the_name', $context);

        $this->assertSame('THE_NAME', $result['value_provider']());
    }
}
