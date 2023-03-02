<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;

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

        $this->assertSame($expectedHook, (new HookExtractor())->forProperty($property, ['hooks' => $hooks]));
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
    public function testExtractFromType(?callable $expectedHook, array $hooks, Type|UnionType $type): void
    {
        $this->assertSame($expectedHook, (new HookExtractor())->extractFromType($type, ['hooks' => $hooks]));
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}, 2: Type|UnionType>
     */
    public function extractFromTypeDataProvider(): iterable
    {
        $createTypeHook = fn () => static function (string $type, string $accessor, array $context) {
        };

        yield [null, [], new Type('int')];
        yield [null, ['other' => $createTypeHook()], new Type('int')];

        yield [$hook = $createTypeHook(), ['foo' => $hook], new Type('foo')];

        $unionType = new UnionType([new Type('int'), new Type('string')]);

        yield [$hook = $createTypeHook(), ['int|string' => $hook], $unionType];
        yield [$hook = $createTypeHook(), ['union' => $hook], $unionType];
        yield [$hook = $createTypeHook(), ['type' => $hook], $unionType];
        yield [$hook = $createTypeHook(), ['int|string' => $hook, 'union' => $createTypeHook()], $unionType];
        yield [$hook = $createTypeHook(), ['union' => $hook, 'type' => $createTypeHook()], $unionType];

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

    public function testExtractFromKey(): void
    {
        $fooHook = static function (\ReflectionClass $class, object $object, string $key, callable $value, array $context): void {
        };
        $barHook = static function (\ReflectionClass $class, object $object, string $key, callable $value, array $context): void {
        };

        $contextWithProperty = [
            'hooks' => [
                'class[foo]' => $fooHook,
                'property' => $barHook,
            ],
        ];

        $contextWithoutProperty = [
            'hooks' => [
                'class[foo]' => $fooHook,
            ],
        ];

        $hookExtractor = new HookExtractor();

        $this->assertSame($barHook, $hookExtractor->forKey('unexistingClass', 'foo', $contextWithProperty));
        $this->assertSame($barHook, $hookExtractor->forKey('class', 'unexistingKey', $contextWithProperty));
        $this->assertSame($fooHook, $hookExtractor->forKey('class', 'foo', $contextWithProperty));

        $this->assertNull($hookExtractor->forKey('unexistingClass', 'foo', $contextWithoutProperty));
        $this->assertNull($hookExtractor->forKey('class', 'unexistingKey', $contextWithoutProperty));
    }
}
