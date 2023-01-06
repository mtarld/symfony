<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Hook\MarshalHookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;

final class MarshalHookExtractorTest extends TestCase
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

        $this->assertSame($expectedHook, (new MarshalHookExtractor())->extractFromProperty($property, ['hooks' => $hooks]));
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}>
     */
    public function extractFromPropertyDataProvider(): iterable
    {
        $createPropertyHook = fn () => static function (\ReflectionProperty $property, string $accessor, array $context) {
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
        $this->assertSame($expectedHook, (new MarshalHookExtractor())->extractFromType($type, ['hooks' => $hooks]));
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}, 2: Type>
     */
    public function extractFromTypeDataProvider(): iterable
    {
        $createTypeHook = fn () => static function (string $type, string $accessor, array $context) {
        };

        yield [null, [], new Type('int')];
        yield [null, ['other' => $createTypeHook()], new Type('int')];

        yield [$hook = $createTypeHook(), ['foo' => $hook], new Type('foo')];

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

        $listType = new Type('array', isNullable: true, isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]);

        yield [$hook = $createTypeHook(), ['?array' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['array' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['list' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['collection' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $listType];
        yield [$hook = $createTypeHook(), ['?array' => $hook, 'array' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['array' => $hook, 'list' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['list' => $hook, 'collection' => $createTypeHook()], $listType];
        yield [$hook = $createTypeHook(), ['collection' => $hook, 'type' => $createTypeHook()], $listType];

        $dictType = new Type('array', isNullable: true, isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);

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

        (new MarshalHookExtractor())->extractFromProperty($property, ['hooks' => ['property' => $callable]]);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: callable}>
     */
    public function propertyHookValidationDataProvider(): iterable
    {
        yield [null, static function (\ReflectionProperty $property, string $accessor, array $context) {
        }];
        yield ['Hook "property" must have exactly 3 arguments.', static function () {
        }];
        yield ['Hook "property" must have a "ReflectionProperty" for first argument.', static function (int $property, string $accessor, array $context) {
        }];
        yield ['Hook "property" must have a "string" for second argument.', static function (\ReflectionProperty $property, int $accessor, array $context) {
        }];
        yield ['Hook "property" must have an "array" for third argument.', static function (\ReflectionProperty $property, string $accessor, int $context) {
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

        (new MarshalHookExtractor())->extractFromType(new Type('int'), ['hooks' => ['type' => $callable]]);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: callable}>
     */
    public function typeHookValidationDataProvider(): iterable
    {
        yield [null, static function (string $type, string $accessor, array $context) {
        }];
        yield ['Hook "type" must have exactly 3 arguments.', static function () {
        }];
        yield ['Hook "type" must have a "string" for first argument.', static function (int $type, string $accessor, array $context) {
        }];
        yield ['Hook "type" must have a "string" for second argument.', static function (string $type, int $accessor, array $context) {
        }];
        yield ['Hook "type" must have an "array" for third argument.', static function (string $type, string $accessor, int $context) {
        }];
    }
}
