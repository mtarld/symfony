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
     * @param array<string, callable> $typeValueFormatters
     */
    public function testGenerateTemplate(string $expectedType, string $expectedAccessor, array $typeValueFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturnCallback(fn (\ReflectionFunctionAbstract $c): string => $c->getReturnType()->getName());

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'type_value_formatter' => $typeValueFormatters,
            ],
            'type_value_template_generator' => fn (string $type, string $accessor, array $context): string => sprintf('%s|%s', $type, $accessor),
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
        $regularAnonymous = function (int $value, array $context): string {
            return (string) (2 * $value);
        };

        $staticAnonymous = static function (int $value, array $context): string {
            return (string) (2 * $value);
        };

        $arrowAnonymous = fn (int $value, array $context): string => (string) (2 * $value);

        yield ['int', '$accessor', []];
        yield ['int', '$accessor', ['string' => strtoupper(...)]];
        yield ['string', 'strtoupper($accessor, $context)', ['int' => strtoupper(...)]];
        yield ['string', '$context[\'symfony\'][\'type_value_formatter\'][\'int\']($accessor, $context)', ['int' => $regularAnonymous]];
        yield ['string', '$context[\'symfony\'][\'type_value_formatter\'][\'int\']($accessor, $context)', ['int' => $staticAnonymous]];
        yield ['string', '$context[\'symfony\'][\'type_value_formatter\'][\'int\']($accessor, $context)', ['int' => $arrowAnonymous]];
        yield ['string', '$context[\'symfony\'][\'type_value_formatter\'][\'int\']($accessor, $context)', ['int' => (new DummyWithMethods())->tripleAndCastToString(...)]];
        yield ['string', sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class), ['int' => DummyWithMethods::doubleAndCastToString(...)]];
    }

    public function testThrowWhenTypeExtractorIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing "$context[\'symfony\'][\'type_extractor\']".');

        (new TypeHook())('type', '$accessor', 'format', []);
    }

    public function testThrowWhenInvalidTypeFormatterContextParameter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromReturnType')->willReturn('string');

        $typeValueFormatters = [
            'int' => fn (int $value, int $context) => (string) (2 * $value),
        ];

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'type_value_formatter' => $typeValueFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument of type value formatter "int" must be an array.');

        (new TypeHook())('int', '$accessor', 'format', $context);
    }
}
