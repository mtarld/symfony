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
    public function testGenerateNameTemplatePart(string $expectedName, array $propertyNames): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'property_name' => $propertyNames,
            ],
            'property_name_template_generator' => fn (string $name): string => $name,
            'property_value_template_generator' => fn (): string => '|PROPERTY_VALUE',
        ];

        $result = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
        [$propertyName, $propertyValue] = explode('|', $result);

        $this->assertSame($expectedName, $propertyName);
        $this->assertSame('PROPERTY_VALUE', $propertyValue);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, string>, 2: array<string, callable>}>
     */
    public function generateNameTemplatePartDataProvider(): iterable
    {
        yield ['\'id\'', []];
        yield ['\'id\'', [sprintf('%s::$name', ClassicDummy::class) => 'identifier']];
        yield ['\'identifier\'', [sprintf('%s::$id', ClassicDummy::class) => 'identifier']];
    }

    /**
     * @dataProvider generateValueTemplatePartDataProvider
     *
     * @param array<string, callable> $propertyFormatters
     * @param array<string, string>   $propertyTypes
     */
    public function testGenerateValueTemplatePart(string $expectedType, string $expectedAccessor, array $propertyFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromProperty')->willReturnCallback(fn (\ReflectionProperty $c): string => $c->getType()->getName());
        $typeExtractor->method('extractFromReturnType')->willReturnCallback(fn (\ReflectionFunctionAbstract $c): string => $c->getReturnType()->getName());

        $hookGeneratedContext = [];

        $context = [
            'symfony' => [
                'property_formatter' => $propertyFormatters,
            ],
            'property_name_template_generator' => fn (): string => 'PROPERTY_NAME|',
            'property_value_template_generator' => static function (string $type, string $accessor, array $context) use (&$hookGeneratedContext): string {
                $hookGeneratedContext = $context;

                return sprintf('%s|%s', $type, $accessor);
            },
        ];

        $result = (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
        [$propertyName, $type, $accessor] = explode('|', $result);

        $this->assertSame('PROPERTY_NAME', $propertyName);
        $this->assertSame($expectedType, $type);
        $this->assertSame($expectedAccessor, $accessor);
        $this->assertSame(ClassicDummy::class, $hookGeneratedContext['symfony']['current_property_class']);
    }

    /**
     * @return iterable<array{0: string, 1: array<string, string>, 2: array<string, callable>}>
     */
    public function generateValueTemplatePartDataProvider(): iterable
    {
        yield ['int', '$accessor', []];
        yield ['int', '$accessor', [sprintf('%s::$name', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)]];
        yield [
            'string',
            sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class),
            [sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::doubleAndCastToString(...)],
        ];
    }

    public function testThrowWhenPropertyIsNotPublic(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public', DummyWithNotPublicProperty::class));

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(DummyWithNotPublicProperty::class, 'name'), '$accessor', 'format', []);
    }

    public function testThrowWhenInvalidPropertyFormatterParametersCount(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::tooManyParameters(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Property formatter "%s::$id" must have exactly two parameters.', ClassicDummy::class));

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }

    public function testThrowWhenInvalidPropertyFormatterContextTypeParameter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::invalidContextType(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Second argument of property formatter "%s::$id" must be an array.', ClassicDummy::class));

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }

    public function testThrowWhenNonStaticMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => (new DummyWithMethods())->nonStatic(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Property formatter "%s::$id" must be a static method.', ClassicDummy::class));

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }

    public function testThrowWhenVoidMethodTypeFormatter(): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            'symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => DummyWithMethods::void(...),
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Return type of property formatter "%s::$id" must not be "void" nor "never".', ClassicDummy::class));

        (new PropertyHook($typeExtractor))(new \ReflectionProperty(ClassicDummy::class, 'id'), '$accessor', 'format', $context);
    }
}
