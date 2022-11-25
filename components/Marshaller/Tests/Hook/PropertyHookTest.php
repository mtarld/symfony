<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Hook\PropertyHook;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNotPublicProperty;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class PropertyHookTest extends TestCase
{
    /**
     * @dataProvider generateNameTemplatePartDataProvider
     *
     * @param array<string, string>   $propertyNames
     * @param array<string, callable> $propertyNameFormatters
     */
    public function testGenerateNameTemplatePart(string $expectedName, array $propertyNames, array $propertyNameFormatters): void
    {
        $context = [
            'symfony' => [
                'type_extractor' => $this->createStub(TypeExtractorInterface::class),
                'property_name' => $propertyNames,
                'property_name_formatter' => $propertyNameFormatters,
            ],
            'property_name_template_generator' => fn (string $name): string => $name,
            'property_value_template_generator' => fn (): string => '|PROPERTY_VALUE',
        ];

        $result = (new PropertyHook())(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
        [$propertyName, $propertyValue] = explode('|', $result);

        $this->assertSame($expectedName, $propertyName);
        $this->assertSame('PROPERTY_VALUE', $propertyValue);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, string>, 2: array<string, callable>}>
     */
    public function generateNameTemplatePartDataProvider(): iterable
    {
        $regularAnonymous = function (string $name, array $context): string {
            return strtoupper($name);
        };

        $staticAnonymous = static function (string $name, array $context): string {
            return strtoupper($name);
        };

        $arrowAnonymous = fn (string $name, array $context): string => strtoupper($name);

        yield ['\'id\'', [], []];
        yield ['\'id\'', [sprintf('%s::$name', ClassicDummy::class) => 'identifier'], [sprintf('%s::$name', ClassicDummy::class) => strtoupper(...)]];
        yield ['\'identifier\'', [sprintf('%s::$id', ClassicDummy::class) => 'identifier'], [sprintf('%s::$id', ClassicDummy::class) => strtoupper(...)]];
        yield ['strtoupper(\'id\', $context)', [], [sprintf('%s::$id', ClassicDummy::class) => strtoupper(...)]];
        yield [
            sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s::$id\'](\'id\', $context)', ClassicDummy::class),
            [],
            [sprintf('%s::$id', ClassicDummy::class) => $regularAnonymous],
        ];
        yield [
            sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s::$id\'](\'id\', $context)', ClassicDummy::class),
            [],
            [sprintf('%s::$id', ClassicDummy::class) => $staticAnonymous],
        ];
        yield [
            sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s::$id\'](\'id\', $context)', ClassicDummy::class),
            [],
            [sprintf('%s::$id', ClassicDummy::class) => $arrowAnonymous],
        ];
        yield [
            sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s::$id\'](\'id\', $context)', ClassicDummy::class),
            [],
            [sprintf('%s::$id', ClassicDummy::class) => (new DummyWithMethods())->tripleAndCastToString(...)],
        ];
        yield [
            sprintf('%s::doubleAndCastToString(\'id\', $context)', DummyWithMethods::class),
            [],
            [sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)],
        ];
    }

    /**
     * @dataProvider generateValueTemplatePartDataProvider
     *
     * @param array<string, callable> $propertyValueFormatters
     * @param array<string, string>   $propertyTypes
     */
    public function testGenerateValueTemplatePart(string $expectedType, string $expectedAccessor, array $propertyValueFormatters, array $propertyTypes): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturnCallback(fn (\ReflectionProperty $c): string => $c->getType()->getName());
        $typeExtractor->method('extractFromReturnType')->willReturnCallback(fn (\ReflectionFunctionAbstract $c): string => $c->getReturnType()->getName());

        $context = [
            'symfony' => [
                'type_extractor' => $typeExtractor,
                'property_value_formatter' => $propertyValueFormatters,
                'property_type' => $propertyTypes,
            ],
            'property_name_template_generator' => fn (): string => 'PROPERTY_NAME|',
            'property_value_template_generator' => fn (string $type, string $accessor, array $context): string => sprintf('%s|%s', $type, $accessor),
        ];

        $result = (new PropertyHook())(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
        [$propertyName, $type, $accessor] = explode('|', $result);

        $this->assertSame('PROPERTY_NAME', $propertyName);
        $this->assertSame($expectedType, $type);
        $this->assertSame($expectedAccessor, $accessor);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, string>, 2: array<string, callable>}>
     */
    public function generateValueTemplatePartDataProvider(): iterable
    {
        $regularAnonymous = function (int $value, array $context): string {
            return (string) (2 * $value);
        };

        $staticAnonymous = static function (int $value, array $context): string {
            return (string) (2 * $value);
        };

        $arrowAnonymous = fn (int $value, array $context): string => (string) (2 * $value);

        yield ['int', '$accessor', [], []];
        yield ['int', '$accessor', [sprintf('%s::$name', ClassicDummy::class) => strtoupper(...)], [sprintf('%s::$name', ClassicDummy::class) => 'string']];
        yield ['int', '$accessor', [sprintf('%s::$name', ClassicDummy::class) => strtoupper(...)], [sprintf('%s::$name', ClassicDummy::class) => 'bool']];
        yield ['bool', '$accessor', [], [sprintf('%s::$id', ClassicDummy::class) => 'bool']];
        yield ['string', 'strtoupper($accessor, $context)', [sprintf('%s::$id', ClassicDummy::class) => strtoupper(...)], []];
        yield ['string', 'strtoupper($accessor, $context)', [sprintf('%s::$id', ClassicDummy::class) => strtoupper(...)], [sprintf('%s::$id', ClassicDummy::class) => 'bool']];
        yield [
            'string',
            sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s::$id\']($accessor, $context)', ClassicDummy::class),
            [sprintf('%s::$id', ClassicDummy::class) => $regularAnonymous],
            [],
        ];
        yield [
            'string',
            sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s::$id\']($accessor, $context)', ClassicDummy::class),
            [sprintf('%s::$id', ClassicDummy::class) => $staticAnonymous],
            [],
        ];
        yield [
            'string',
            sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s::$id\']($accessor, $context)', ClassicDummy::class),
            [sprintf('%s::$id', ClassicDummy::class) => $arrowAnonymous],
            [],
        ];
        yield [
            'string',
            sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s::$id\']($accessor, $context)', ClassicDummy::class),
            [sprintf('%s::$id', ClassicDummy::class) => (new DummyWithMethods())->tripleAndCastToString(...)],
            [],
        ];
        yield [
            'string',
            sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            [sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)],
            [],
        ];
    }

    public function testThrowWhenTypeExtractorIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing "$context[\'symfony\'][\'type_extractor\']".');

        (new PropertyHook())(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', []);
    }

    public function testThrowWhenPropertyIsNotPublic(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public', DummyWithNotPublicProperty::class));

        $context = [
            'symfony' => [
                'type_extractor' => $this->createStub(TypeExtractorInterface::class),
            ],
        ];

        (new PropertyHook())(new \ReflectionProperty(DummyWithNotPublicProperty::class, 'name'), '$accessor', 'format', $context);
    }

    public function testThrowWhenInvalidPropertyNameFormatterContextParameter(): void
    {
        $propertyNameFormatters = [
            sprintf('%s::$id', ClassicDummy::class) => fn (string $name, int $context) => strtoupper($name),
        ];

        $context = [
            'symfony' => [
                'type_extractor' => $this->createStub(TypeExtractorInterface::class),
                'property_name_formatter' => $propertyNameFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Second argument of property name formatter "%s::$id" must be an array.', ClassicDummy::class));

        (new PropertyHook())(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }

    public function testThrowWhenInvalidPropertyValueFormatterContextParameter(): void
    {
        $propertyValueFormatters = [
            sprintf('%s::$id', ClassicDummy::class) => fn (int $id, int $context) => (string) (2 * $id),
        ];

        $context = [
            'symfony' => [
                'type_extractor' => $this->createStub(TypeExtractorInterface::class),
                'property_value_formatter' => $propertyValueFormatters,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Second argument of property value formatter "%s::$id" must be an array.', ClassicDummy::class));

        (new PropertyHook())(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }
}
