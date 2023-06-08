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
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Hook\Deserialize\ObjectHook;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\SerDes\Type\PhpstanTypeExtractor;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;
use Symfony\Component\SerDes\Type\TypeFactory;

class ObjectHookTest extends TestCase
{
    /**
     * @dataProvider addGenericParameterTypesDataProvider
     *
     * @param array<class-string, array<string, string>> $expectedGenericParameterTypes
     * @param list<string>                               $templates
     */
    public function testAddGenericParameterTypes(array $expectedGenericParameterTypes, string $type, array $templates)
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn($templates);

        $hookResult = (new ObjectHook($typeExtractor))(TypeFactory::createFromString($type), [], []);

        $this->assertSame($expectedGenericParameterTypes, $hookResult['context']['_symfony']['generic_parameter_types'] ?? []);
    }

    /**
     * @return iterable<array{0: array<class-string, array<string, string>>, 1: string, 2: list<string>}>
     */
    public static function addGenericParameterTypesDataProvider(): iterable
    {
        yield [[], ClassicDummy::class, []];
        yield [[ClassicDummy::class => ['T' => TypeFactory::createFromString('int')]], ClassicDummy::class.'<int>', ['T']];
        yield [
            [ClassicDummy::class => ['Tk' => TypeFactory::createFromString('int'), 'Tv' => TypeFactory::createFromString('string')]],
            ClassicDummy::class.'<int, string>',
            ['Tk', 'Tv'],
        ];
    }

    public function testThrowOnWrongGenericTypeCount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Given 1 generic parameters in "%s<int>", but 2 templates are defined in "%1$s".', ClassicDummy::class));

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn(['Tk', 'Tv']);

        (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class.'<int>'), [], []);
    }

    public function testRetrievePropertyName()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));

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

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['@id' => [
            'name' => 'id',
            'value_provider' => fn (Type $t) => null,
        ]], $context);

        $this->assertSame('id', $result['properties']['@id']['name']);
    }

    public function testSkipWhenNoGroupIsMatching()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'groups' => ['one'],
            '_symfony' => [
                'deserialize' => [
                    'property_groups' => [
                        ClassicDummy::class => [
                            'id' => ['two' => true],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['id' => [
            'name' => 'id',
            'value_provider' => fn (Type $t) => null,
        ]], $context);

        $this->assertArrayNotHasKey('id', $result['properties']);
    }

    public function testDoNotSkipWhenGroupIsMatching()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));

        $context = [
            'groups' => ['one', 'two'],
            '_symfony' => [
                'deserialize' => [
                    'property_groups' => [
                        ClassicDummy::class => [
                            'id' => ['one' => true, 'three' => true],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['id' => [
            'name' => 'id',
            'value_provider' => fn (Type $t) => null,
        ]], $context);

        $this->assertArrayHasKey('id', $result['properties']);
    }

    public function testRetrievePropertyType()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $type = null;
        $valueProvider = static function (Type $valueType) use (&$type) {
            $type = $valueType;
        };

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['name' => [
            'name' => 'name',
            'value_provider' => $valueProvider,
        ]], []);

        $result['properties']['name']['value_provider'](TypeFactory::createFromString('int'));

        $this->assertEquals(TypeFactory::createFromString('string'), $type);
    }

    public function testRetrievePropertyTypeWithGenerics()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithGenerics::class => ['T' => TypeFactory::createFromString(ClassicDummy::class)],
                ],
            ],
        ];

        $type = null;
        $valueProvider = static function (Type $valueType) use (&$type) {
            $type = $valueType;
        };

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(sprintf('%s<%s>', DummyWithGenerics::class, ClassicDummy::class)), ['dummies' => [
            'name' => 'dummies',
            'value_provider' => $valueProvider,
        ]], $context);

        $result['properties']['dummies']['value_provider'](TypeFactory::createFromString('int'));

        $this->assertEquals(TypeFactory::createFromString(sprintf('array<int, %s>', ClassicDummy::class)), $type);
    }

    public function testRetrievePropertyTypeWithFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $type = null;
        $valueProvider = static function (Type $valueType) use (&$type): int {
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

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['name' => [
            'name' => 'name',
            'value_provider' => $valueProvider,
        ]], $context);

        $result['properties']['name']['value_provider'](TypeFactory::createFromString('string'));

        $this->assertEquals(TypeFactory::createFromString('int'), $type);
    }

    public function testRetrievePropertyTypeWithFormatterAndGenerics()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn(TypeFactory::createFromString('T'));

        $type = null;
        $valueProvider = static function (Type $valueType) use (&$type): int {
            $type = $valueType;

            return 123;
        };

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithFormatterAttributes::class => ['T' => TypeFactory::createFromString('string')],
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

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(DummyWithFormatterAttributes::class), ['name' => [
            'name' => 'name',
            'value_provider' => $valueProvider,
        ]], $context);

        $result['properties']['name']['value_provider'](TypeFactory::createFromString('int'));

        $this->assertEquals(TypeFactory::createFromString('string'), $type);
    }

    public function testDoNotReplaceGenericTypesWhenFormatterDoesNotBelongToCurrentClass()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionParameter')->willReturn(TypeFactory::createFromString('T'));

        $type = null;
        $valueProvider = static function (Type $valueType) use (&$type): int {
            $type = $valueType;

            return 123;
        };

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    DummyWithQuotes::class => ['T' => TypeFactory::createFromString('string')],
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

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(DummyWithQuotes::class), ['name' => [
            'name' => 'name',
            'value_provider' => $valueProvider,
        ]], $context);

        $result['properties']['name']['value_provider'](TypeFactory::createFromString('int'));

        $this->assertEquals(TypeFactory::createFromString('T'), $type);
    }

    public function testFormatValue()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));

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

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), ['name' => [
            'name' => 'name',
            'value_provider' => fn (Type $t) => 'the_name',
        ]], $context);

        $this->assertSame('THE_NAME', $result['properties']['name']['value_provider'](TypeFactory::createFromString('string')));
    }
}
