<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHook;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeFactory;

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

        $hookResult = (new ObjectHook($typeExtractor))(TypeFactory::createFromString($type), 'accessor', [], []);

        $this->assertEquals($expectedGenericParameterTypes, $hookResult['context']['_symfony']['generic_parameter_types'] ?? []);
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

        (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class.'<int>'), 'accessor', [], []);
    }

    /**
     * @dataProvider updateNameDataProvider
     *
     * @param array<string, string> $propertyNames
     */
    public function testUpdateName(string $expectedName, array $propertyNames)
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));

        $context = [
            '_symfony' => [
                'serialize' => [
                    'property_name' => $propertyNames,
                ],
            ],
        ];

        $hookResult = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), 'accessor', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$object->id',
        ]], $context);

        $this->assertSame($expectedName, $hookResult['properties']['id']['name']);
    }

    /**
     * @return iterable<array{0: string, 1: array<class-string, array<string, string>>}>
     */
    public static function updateNameDataProvider(): iterable
    {
        yield ['id', []];
        yield ['id', [ClassicDummy::class => ['name' => 'identifier']]];
        yield ['identifier', [ClassicDummy::class => ['id' => 'identifier']]];
    }

    /**
     * @dataProvider updateTypeAccessorAndContextFromFormatterDataProvider
     *
     * @param array<string, callable> $propertyFormatters
     */
    public function testUpdateTypeAccessorAndContextFromFormatter(string $expectedType, string $expectedAccessor, array $propertyFormatters)
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));
        $typeExtractor->method('extractFromFunctionReturn')->willReturn(TypeFactory::createFromString('string'));

        $context = [
            '_symfony' => [
                'serialize' => [
                    'property_formatter' => $propertyFormatters,
                ],
            ],
        ];

        $hookResult = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$accessor',
        ]], $context);

        $this->assertEquals($expectedType, $hookResult['properties']['id']['type']);
        $this->assertSame($expectedAccessor, $hookResult['properties']['id']['accessor']);
    }

    /**
     * @return iterable<array{0: string, 1: array<class-string, array<string, callable>}>>
     */
    public static function updateTypeAccessorAndContextFromFormatterDataProvider(): iterable
    {
        yield ['int', '$accessor', []];
        yield ['int', '$accessor', [ClassicDummy::class => ['name' => DummyWithMethods::doubleAndCastToString(...)]]];
        yield [
            'string',
            sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            [ClassicDummy::class => ['id' => DummyWithMethods::doubleAndCastToString(...)]],
        ];
    }

    /**
     * @dataProvider throwWhenWrongFormatterDataProvider
     */
    public function testThrowWhenWrongFormatter(string $exceptionMessage, callable $formatter)
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionReturn')->willReturn(TypeFactory::createFromString('int'));

        $context = [
            '_symfony' => [
                'serialize' => [
                    'property_formatter' => [
                        ClassicDummy::class => ['id' => $formatter],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$accessor',
        ]], $context);
    }

    /**
     * @return iterable<array{0: string, 1: callable}>
     */
    public static function throwWhenWrongFormatterDataProvider(): iterable
    {
        yield [
            sprintf('Property formatter "%s::$id" must be a static method.', ClassicDummy::class),
            (new DummyWithMethods())->nonStatic(...),
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

    public function testConvertGenericTypes()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('T'));
        $typeExtractor->method('extractFromFunctionReturn')->willReturn(TypeFactory::createFromString('U'));

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    ClassicDummy::class => ['T' => TypeFactory::createFromString('string')],
                    DummyWithMethods::class => ['U' => TypeFactory::createFromString('int')],
                ],
                'serialize' => [
                    'property_formatter' => [
                        DummyWithMethods::class => [
                            'id' => DummyWithMethods::doubleAndCastToString(...),
                        ],
                    ],
                ],
            ],
        ];

        $hook = new ObjectHook($typeExtractor);

        $this->assertSame(TypeFactory::createFromString('string'), $hook(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('string'),
            'accessor' => '$accessor',
        ]], $context)['properties']['id']['type']);

        $this->assertSame(TypeFactory::createFromString('int'), $hook(TypeFactory::createFromString(DummyWithMethods::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('string'),
            'accessor' => '$accessor',
        ]], $context)['properties']['id']['type']);

        $this->assertSame(TypeFactory::createFromString('T'), $hook(TypeFactory::createFromString(DummyWithQuotes::class), 'useless', ['name' => [
            'name' => 'name',
            'type' => TypeFactory::createFromString('string'),
            'accessor' => '$accessor',
        ]], $context)['properties']['name']['type']);
    }

    public function testDoNotConvertGenericTypesWhenFormatterDoesNotBelongToCurrentClass()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionReturn')->willReturn(TypeFactory::createFromString('T'));

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    ClassicDummy::class => ['T' => TypeFactory::createFromString('string')],
                ],
                'serialize' => [
                    'property_formatter' => [
                        ClassicDummy::class => [
                            'id' => DummyWithMethods::doubleAndCastToString(...),
                        ],
                    ],
                ],
            ],
        ];

        $hook = new ObjectHook($typeExtractor);

        $this->assertSame(TypeFactory::createFromString('T'), $hook(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$accessor',
        ]], $context)['properties']['id']['type']);
    }

    public function testSkipWhenNoGroupIsMatching()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'groups' => ['one'],
            '_symfony' => [
                'serialize' => [
                    'property_groups' => [
                        ClassicDummy::class => [
                            'id' => ['two' => true],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$accessor',
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
                'serialize' => [
                    'property_groups' => [
                        ClassicDummy::class => [
                            'id' => ['one' => true, 'three' => true],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new ObjectHook($typeExtractor))(TypeFactory::createFromString(ClassicDummy::class), 'useless', ['id' => [
            'name' => 'id',
            'type' => TypeFactory::createFromString('int'),
            'accessor' => '$accessor',
        ]], $context);

        $this->assertArrayHasKey('id', $result['properties']);
    }
}
