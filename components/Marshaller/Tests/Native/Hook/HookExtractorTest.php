<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Hook\HookExtractor;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class HookExtractorTest extends TestCase
{
    /**
     * @dataProvider extractFromPropertyDataProvider
     *
     * @param array<string, callable> $hooks
     */
    public function testExtractFromProperty(?callable $expectedHook, array $hooks): void
    {
        $class = $this->createStub(\ReflectionClass::class);
        $class->method('getName')->willReturn('Foo');

        $property = $this->createStub(\ReflectionProperty::class);
        $property->method('getName')->willReturn('bar');
        $property->method('getDeclaringClass')->willReturn($class);

        $this->assertSame($expectedHook, (new HookExtractor())->extractFromProperty($property, ['hooks' => $hooks]));
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}>
     */
    public function extractFromPropertyDataProvider(): iterable
    {
        $createPropertyHook = fn () => static function (\ReflectionProperty $property, string $accessor, string $format, array $context) {
        };

        yield [null, []];
        yield [$hook = $createPropertyHook(), ['property' => $hook]];
        yield [$hook = $createPropertyHook(), ['Foo::$bar' => $hook]];
        yield [$hook = $createPropertyHook(), ['Foo::$bar' => $hook, 'property' => $createPropertyHook()]];
    }

    /**
     * @dataProvider extractFromTypeDataProvider
     *
     * @param array<string, callable> $hooks
     */
    public function testExtractFromType(?callable $expectedHook, array $hooks, Type $type): void
    {
        $this->assertSame($expectedHook, (new HookExtractor())->extractFromType($type, ['hooks' => $hooks]));
    }

    public function testExtractFromTypeThrowOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown "foo" type.');

        (new HookExtractor())->extractFromType(new Type('foo'), []);
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}, 2: Type>
     */
    public function extractFromTypeDataProvider(): iterable
    {
        $createTypeHook = fn () => static function (string $type, string $accessor, string $format, array $context) {
        };

        yield [null, [], new Type('int')];
        yield [null, ['other' => $createTypeHook()], new Type('int')];

        $nullType = new Type('null');

        yield [$hook = $createTypeHook(), ['null' => $hook], $nullType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $nullType];
        yield [$hook = $createTypeHook(), ['null' => $hook, 'type' => $createTypeHook()], $nullType];

        $scalarType = new Type('int', isNullable: true);

        yield [$hook = $createTypeHook(), ['?int' => $hook], $scalarType];
        yield [$hook = $createTypeHook(), ['int' => $hook], $scalarType];
        yield [$hook = $createTypeHook(), ['scalar' => $hook], $scalarType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $scalarType];
        yield [$hook = $createTypeHook(), ['?int' => $hook, 'int' => $createTypeHook()], $scalarType];
        yield [$hook = $createTypeHook(), ['int' => $hook, 'scalar' => $createTypeHook()], $scalarType];
        yield [$hook = $createTypeHook(), ['scalar' => $hook, 'type' => $createTypeHook()], $scalarType];

        $objectType = new Type('object', isNullable: true, className: ClassicDummy::class);

        yield [$hook = $createTypeHook(), ['?'.ClassicDummy::class => $hook], $objectType];
        yield [$hook = $createTypeHook(), [ClassicDummy::class => $hook], $objectType];
        yield [$hook = $createTypeHook(), ['object' => $hook], $objectType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $objectType];
        yield [$hook = $createTypeHook(), ['?'.ClassicDummy::class => $hook, ClassicDummy::class => $createTypeHook()], $objectType];
        yield [$hook = $createTypeHook(), [ClassicDummy::class => $hook, 'object' => $createTypeHook()], $objectType];
        yield [$hook = $createTypeHook(), ['object' => $hook, 'type' => $createTypeHook()], $objectType];

        $listType = new Type('array', isNullable: true, collectionKeyType: new Type('int'), collectionValueType: new Type('int'));

        yield [$hook = $createTypeHook(), ['?array' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['array' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['list' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['collection' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['?array' => $hook, 'array' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['array' => $hook, 'list' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['list' => $hook, 'collection' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['collection' => $hook, 'type' => $createTypeHook()], $listType];

        $dictType = new Type('array', isNullable: true, collectionKeyType: new Type('string'), collectionValueType: new Type('int'));

        yield [$hook = $createTypeHook(), ['?array' => $hook], $dictType];
        yield [$hook = $createTypeHook(), ['array' => $hook], $dictType];
        yield [$hook = $createTypeHook(), ['dict' => $hook], $dictType];
        yield [$hook = $createTypeHook(), ['collection' => $hook], $dictType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $dictType];
        yield [$hook = $createTypeHook(), ['?array' => $hook, 'array' => $createTypeHook()], $dictType];
        yield [$hook = $createTypeHook(), ['array' => $hook, 'dict' => $createTypeHook()], $dictType];
        yield [$hook = $createTypeHook(), ['dict' => $hook, 'collection' => $createTypeHook()], $dictType];
        yield [$hook = $createTypeHook(), ['collection' => $hook, 'type' => $createTypeHook()], $dictType];
    }

    /**
     * @dataProvider propertyHookValidationDataProvider
     */
    public function testPropertyHookValidation(?string $expectedExceptionMessage, callable $callable): void
    {
        $class = $this->createStub(\ReflectionClass::class);
        $class->method('getName')->willReturn('Foo');

        $property = $this->createStub(\ReflectionProperty::class);
        $property->method('getName')->willReturn('bar');
        $property->method('getDeclaringClass')->willReturn($class);

        if (null !== $expectedExceptionMessage) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        (new HookExtractor())->extractFromProperty($property, ['hooks' => ['property' => $callable]]);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: callable}>
     */
    public function propertyHookValidationDataProvider(): iterable
    {
        yield [null, static function (\ReflectionProperty $property, string $accessor, string $format, array $context) {
        }];
        yield ['Hook "property" must have exactly 4 arguments.', static function () {
        }];
        yield ['Hook "property" must have a "ReflectionProperty" for first argument.', static function (int $property, string $accessor, string $format, array $context) {
        }];
        yield ['Hook "property" must have a "string" for second argument.', static function (\ReflectionProperty $property, int $accessor, string $format, array $context) {
        }];
        yield ['Hook "property" must have a "string" for third argument.', static function (\ReflectionProperty $property, string $accessor, int $format, array $context) {
        }];
        yield ['Hook "property" must have an "array" for fourth argument.', static function (\ReflectionProperty $property, string $accessor, string $format, int $context) {
        }];
    }

    /**
     * @dataProvider typeHookValidationDataProvider
     */
    public function testTypeHookValidation(?string $expectedExceptionMessage, callable $callable): void
    {
        if (null !== $expectedExceptionMessage) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        (new HookExtractor())->extractFromType(new Type('int'), ['hooks' => ['type' => $callable]]);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: callable}>
     */
    public function typeHookValidationDataProvider(): iterable
    {
        yield [null, static function (string $type, string $accessor, string $format, array $context) {
        }];
        yield ['Hook "type" must have exactly 4 arguments.', static function () {
        }];
        yield ['Hook "type" must have a "string" for first argument.', static function (int $type, string $accessor, string $format, array $context) {
        }];
        yield ['Hook "type" must have a "string" for second argument.', static function (string $type, int $accessor, string $format, array $context) {
        }];
        yield ['Hook "type" must have a "string" for third argument.', static function (string $type, string $accessor, int $format, array $context) {
        }];
        yield ['Hook "type" must have an "array" for fourth argument.', static function (string $type, string $accessor, string $format, int $context) {
        }];
    }
}
