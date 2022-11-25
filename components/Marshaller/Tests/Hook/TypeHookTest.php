<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Hook\TypeHook;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithMethods;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class TypeHookTest extends TestCase
{
    /**
     * @dataProvider generateTemplateDataProvider
     *
     * @param array<string, callable> $typeFormatters
     */
    public function testGenerateTemplate(string $expectedType, string $expectedAccessor, array $typeFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturnCallback(fn (\ReflectionFunctionAbstract $c): string => $c->getReturnType()->getName());

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'type_formatter' => $typeFormatters,
            ],
            'type_template_generator' => fn (string $type, string $accessor, array $context): string => sprintf('%s|%s', $type, $accessor),
        ];

        $result = (new TypeHook())('int', '$accessor', 'format', $context);
        [$type, $accessor] = explode('|', $result);

        $this->assertSame($expectedType, $type);
        $this->assertSame($expectedAccessor, $accessor);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, callable>}>
     */
    public function generateTemplateDataProvider(): iterable
    {
        yield ['int', '$accessor', []];
        yield ['int', '$accessor', ['string' => DummyWithMethods::doubleAndCastToString(...)]];
        yield ['string', sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class), ['int' => DummyWithMethods::doubleAndCastToString(...)]];
    }

    public function testThrowWhenTypeExtractorIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing "$context[\'symfony\'][\'type_extractor\']".');

        (new TypeHook())('type', '$accessor', 'format', []);
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
                'type_extractor' => $typeExtractor,
                'type_formatter' => $typeFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must have exactly two parameters.');

        (new TypeHook())('int', '$accessor', 'format', $context);
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
                'type_extractor' => $typeExtractor,
                'type_formatter' => $typeFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument of type formatter "int" must be an array.');

        (new TypeHook())('int', '$accessor', 'format', $context);
    }

    public function testThrowWhenNonStaticMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'type_formatter' => [
                    'int' => (new DummyWithMethods())->nonStatic(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type formatter "int" must be a static method.');

        (new TypeHook())('int', '$accessor', 'format', $context);
    }

    public function testThrowWhenVoidMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'type_formatter' => [
                    'int' => DummyWithMethods::void(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Return type of type formatter "int" must not be "void" nor "never".');

        (new TypeHook())('int', '$accessor', 'format', $context);
    }
}
