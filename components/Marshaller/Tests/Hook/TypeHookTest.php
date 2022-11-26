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
     * @dataProvider generateTemplateDataProvider
     *
     * @param array<string, callable>                    $typeFormatters
     * @param array<class-string, array<string, string>> $genericParameterTypes
     */
    public function testGenerateTemplate(string $expectedType, string $expectedAccessor, string $type, array $typeFormatters, ?string $returnType, ?string $currentPropertyClass, array $genericParameterTypes): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn($returnType ?? 'UNDEFINED');

        $context = [
            'symfony' => [
                'type_formatter' => $typeFormatters,
                'generic_parameter_types' => $genericParameterTypes,
            ],
            'type_template_generator' => fn (string $type, string $accessor, array $context): string => sprintf('%s|%s', $type, $accessor),
        ];

        if (null !== $currentPropertyClass) {
            $context['symfony']['current_property_class'] = $currentPropertyClass;
        }

        $result = (new TypeHook($typeExtractor))($type, '$accessor', 'format', $context);
        [$type, $accessor] = explode('|', $result);

        $this->assertSame($expectedType, $type);
        $this->assertSame($expectedAccessor, $accessor);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, callable>}>
     */
    public function generateTemplateDataProvider(): iterable
    {
        yield [
            'expectedType' => 'int',
            'expectedAccessor' => '$accessor',
            'type' => 'int',
            'typeFormatters' => [],
            'returnType' => null,
            'currentPropertyClass' => null,
            'genericParameterTypes' => [],
        ];

        yield [
            'expectedType' => 'int',
            'expectedAccessor' => '$accessor',
            'type' => 'int',
            'typeFormatters' => ['string' => DummyWithMethods::doubleAndCastToString(...)],
            'returnType' => null,
            'currentPropertyClass' => null,
            'genericParameterTypes' => [],
        ];

        yield [
            'expectedType' => 'string',
            'expectedAccessor' => sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            'type' => 'int',
            'typeFormatters' => ['int' => DummyWithMethods::doubleAndCastToString(...)],
            'returnType' => 'string',
            'currentPropertyClass' => null,
            'genericParameterTypes' => [],
        ];

        yield [
            'expectedType' => 'string',
            'expectedAccessor' => '$accessor',
            'type' => 'T',
            'typeFormatters' => [],
            'returnType' => null,
            'currentPropertyClass' => ClassicDummy::class,
            'genericParameterTypes' => [ClassicDummy::class => ['T' => 'string']],
        ];

        yield [
            'expectedType' => 'T',
            'expectedAccessor' => '$accessor',
            'type' => 'T',
            'typeFormatters' => [],
            'returnType' => null,
            'currentPropertyClass' => null,
            'genericParameterTypes' => [ClassicDummy::class => ['T' => 'string']],
        ];

        yield [
            'expectedType' => 'T',
            'expectedAccessor' => '$accessor',
            'type' => 'T',
            'typeFormatters' => [],
            'returnType' => null,
            'currentPropertyClass' => ClassicDummy::class,
            'genericParameterTypes' => [DummyWithMethods::class => ['T' => 'string']],
        ];

        yield [
            'expectedType' => 'T',
            'expectedAccessor' => '$accessor',
            'type' => 'T',
            'typeFormatters' => [],
            'returnType' => null,
            'currentPropertyClass' => ClassicDummy::class,
            'genericParameterTypes' => [DummyWithMethods::class => ['T' => 'string']],
        ];

        yield [
            'expectedType' => 'T',
            'expectedAccessor' => sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            'type' => 'int',
            'typeFormatters' => ['int' => DummyWithMethods::doubleAndCastToString(...)],
            'returnType' => 'T',
            'currentPropertyClass' => ClassicDummy::class,
            'genericParameterTypes' => [DummyWithMethods::class => ['T' => 'string']],
        ];

        yield [
            'expectedType' => 'string',
            'expectedAccessor' => sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            'type' => 'int',
            'typeFormatters' => ['int' => DummyWithMethods::doubleAndCastToString(...)],
            'returnType' => 'T',
            'currentPropertyClass' => DummyWithMethods::class,
            'genericParameterTypes' => [DummyWithMethods::class => ['T' => 'string']],
        ];
    }

    public function testThrowWhenInvalidTypeFormatterParametersCount(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $typeFormatters = [
            'int' => DummyWithMethods::tooManyParameters(...),
        ];

        $context = [
            'symfony' => [
                'type_formatter' => $typeFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must have exactly two parameters.');

        (new TypeHook($typeExtractor))('int', '$accessor', 'format', $context);
    }

    public function testThrowWhenInvalidTypeFormatterContextTypeParameter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $typeFormatters = [
            'int' => DummyWithMethods::invalidContextType(...),
        ];

        $context = [
            'symfony' => [
                'type_formatter' => $typeFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument of type formatter "int" must be an array.');

        (new TypeHook($typeExtractor))('int', '$accessor', 'format', $context);
    }

    public function testThrowWhenNonStaticMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $context = [
            'symfony' => [
                'type_formatter' => [
                    'int' => (new DummyWithMethods())->nonStatic(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must be a static method.');

        (new TypeHook($typeExtractor))('int', '$accessor', 'format', $context);
    }

    public function testThrowWhenVoidMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $context = [
            'symfony' => [
                'type_formatter' => [
                    'int' => DummyWithMethods::void(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Return type of type formatter "int" must not be "void" nor "never".');

        (new TypeHook($typeExtractor))('int', '$accessor', 'format', $context);
    }
}
