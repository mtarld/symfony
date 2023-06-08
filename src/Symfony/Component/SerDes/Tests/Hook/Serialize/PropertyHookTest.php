<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Hook\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Hook\Serialize\PropertyHook;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;
use Symfony\Component\SerDes\Type\TypeFactory;

class PropertyHookTest extends TestCase
{
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

        $hookResult = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertSame($expectedName, $hookResult['name']);
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

        $hookResult = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertSame($expectedType, $hookResult['type']);
        $this->assertSame($expectedAccessor, $hookResult['accessor']);
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

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);
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
                    ClassicDummy::class => ['T' => 'string'],
                    DummyWithMethods::class => ['U' => 'int'],
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

        $hook = new PropertyHook($typeExtractor);

        $this->assertSame('string', $hook(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context)['type']);
        $this->assertSame('int', $hook(new \ReflectionProperty(DummyWithMethods::class, 'id'), '$accessor', $context)['type']);
        $this->assertSame('T', $hook(new \ReflectionProperty(DummyWithQuotes::class, 'name'), '$accessor', $context)['type']);
    }

    public function testDoNotConvertGenericTypesWhenFormatterDoesNotBelongToCurrentClass()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionReturn')->willReturn(TypeFactory::createFromString('T'));

        $context = [
            '_symfony' => [
                'generic_parameter_types' => [
                    ClassicDummy::class => ['T' => 'string'],
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

        $hook = new PropertyHook($typeExtractor);

        $this->assertSame('T', $hook(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context)['type']);
    }

    public function testSkipWhenNoGroupIsMatching()
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn(TypeFactory::createFromString('int'));

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

        $result = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertNull($result['accessor']);
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

        $result = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertNotNull($result['accessor']);
    }
}
