<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithMethods;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class TypeHookTest extends TestCase
{
    /**
     * @dataProvider updateTypeAndAccessorFromFormatterDataProvider
     *
     * @param array<string, callable> $typeFormatters
     */
    public function testUpdateTypeAndAccessorFromFormatter(string $expectedType, string $expectedAccessor, string $formatterReturnType, array $typeFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn($formatterReturnType);

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => $typeFormatters,
                ],
            ],
        ];

        $hookResult = (new TypeHook($typeExtractor))('int', '$accessor', $context);

        $this->assertSame($expectedType, $hookResult['type']);
        $this->assertSame($expectedAccessor, $hookResult['accessor']);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string, 3: array<string, callable>}
     */
    public function updateTypeAndAccessorFromFormatterDataProvider(): iterable
    {
        yield ['int', '$accessor', 'string', []];
        yield ['int', '$accessor', 'string', ['string' => DummyWithMethods::doubleAndCastToString(...)]];
        yield ['string', sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class), 'string', ['int' => DummyWithMethods::doubleAndCastToString(...)]];
    }

    public function testConvertGenericTypes(): void
    {
        $hook = new TypeHook($this->createStub(TypeExtractorInterface::class));

        $context = [
            'symfony' => [
                'marshal' => [
                    'current_property_class' => ClassicDummy::class,
                    'generic_parameter_types' => [ClassicDummy::class => ['T' => 'string']],
                ],
            ],
        ];

        $this->assertSame('string', $hook('T', '$accessor', $context)['type']);

        $context['symfony']['marshal']['current_property_class'] = DummyWithMethods::class;
        $this->assertSame('T', $hook('T', '$accessor', $context)['type']);
    }

    public function testDoNotConvertGenericTypesWhenFormatterDoesNotBelongToCurrentClass(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('T');

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => ['int' => DummyWithMethods::doubleAndCastToString(...)],
                    'current_property_class' => DummyWithMethods::class,
                    'generic_parameter_types' => [DummyWithMethods::class => ['T' => 'string']],
                ],
            ],
        ];
        $this->assertSame('string', (new TypeHook($typeExtractor))('int', '$accessor', $context)['type']);

        $context['symfony']['marshal']['current_property_class'] = ClassicDummy::class;
        $this->assertSame('T', (new TypeHook($typeExtractor))('T', '$accessor', $context)['type']);
    }

    public function testThrowWhenInvalidTypeFormatterParametersCount(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $typeFormatters = [
            'int' => DummyWithMethods::tooManyParameters(...),
        ];

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => $typeFormatters,
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must have exactly two parameters.');

        (new TypeHook($typeExtractor))('int', '$accessor', $context);
    }

    public function testThrowWhenInvalidTypeFormatterContextTypeParameter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $typeFormatters = [
            'int' => DummyWithMethods::invalidContextType(...),
        ];

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => $typeFormatters,
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument of type formatter "int" must be an array.');

        (new TypeHook($typeExtractor))('int', '$accessor', $context);
    }

    public function testThrowWhenNonStaticMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => [
                        'int' => (new DummyWithMethods())->nonStatic(...),
                    ],
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must be a static method.');

        (new TypeHook($typeExtractor))('int', '$accessor', $context);
    }

    public function testThrowWhenVoidMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => [
                        'int' => DummyWithMethods::void(...),
                    ],
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Return type of type formatter "int" must not be "void" nor "never".');

        (new TypeHook($typeExtractor))('int', '$accessor', $context);
    }
}
