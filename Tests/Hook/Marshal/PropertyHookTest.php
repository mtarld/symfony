<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Hook\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Hook\Marshal\PropertyHook;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class PropertyHookTest extends TestCase
{
    /**
     * @dataProvider updateNameDataProvider
     *
     * @param array<string, string> $propertyNames
     */
    public function testUpdateName(string $expectedName, array $propertyNames): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'marshal' => [
                    'property_name' => $propertyNames,
                ],
            ],
        ];

        $hookResult = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertSame($expectedName, $hookResult['name']);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, string>}>
     */
    public function updateNameDataProvider(): iterable
    {
        yield ['id', []];
        yield ['id', [sprintf('%s::$name', ClassicDummy::class) => 'identifier']];
        yield ['identifier', [sprintf('%s::$id', ClassicDummy::class) => 'identifier']];
    }

    /**
     * @dataProvider updateTypeAccessorAndContextFromFormatterDataProvider
     *
     * @param array<string, callable> $propertyFormatters
     */
    public function testUpdateTypeAccessorAndContextFromFormatter(string $expectedType, string $expectedAccessor, array $propertyFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturn('int');
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $context = [
            'symfony' => [
                'marshal' => [
                    'property_formatter' => $propertyFormatters,
                ],
            ],
        ];

        $hookResult = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);

        $this->assertSame($expectedType, $hookResult['type']);
        $this->assertSame($expectedAccessor, $hookResult['accessor']);
        $this->assertSame(ClassicDummy::class, $hookResult['context']['symfony']['marshal']['current_property_class']);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, callable>}>
     */
    public function updateTypeAccessorAndContextFromFormatterDataProvider(): iterable
    {
        yield ['int', '$accessor', []];
        yield ['int', '$accessor', [sprintf('%s::$name', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)]];
        yield [
            'string',
            sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            [sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)],
        ];
    }

    /**
     * @dataProvider throwWhenWrongFormatterDataProvider
     */
    public function testThrowWhenWrongFormatter(string $exceptionMessage, callable $formatter): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'marshal' => [
                    'property_formatter' => [
                        sprintf('%s::$id', ClassicDummy::class) => $formatter,
                    ],
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', $context);
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
            sprintf('Return type of property formatter "%s::$id" must not be "void" nor "never".', ClassicDummy::class),
            DummyWithMethods::void(...),
        ];

        yield [
            sprintf('Second argument of property formatter "%s::$id" must be an array.', ClassicDummy::class),
            DummyWithMethods::invalidContextType(...),
        ];
    }
}
