<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\HookExtractor;

final class HookExtractorTest extends TestCase
{
    /**
     * @dataProvider forPropertyDataProvider
     *
     * @param array<string, callable> $hooks
     */
    public function testForProperty(?callable $expectedHook, array $hooks): void
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
    public function forPropertyDataProvider(): iterable
    {
        $createHook = fn () => static function () {};

        yield [null, []];
        yield [$hook = $createHook(), ['property' => $hook]];
        yield [$hook = $createHook(), ['Foo::$bar' => $hook]];
        yield [$hook = $createHook(), ['Foo::$bar' => $hook, 'property' => $createHook()]];
    }

    /**
     * @dataProvider forObjectDataProvider
     *
     * @param array<string, callable> $hooks
     */
    public function testForObject(?callable $expectedHook, array $hooks): void
    {
        $this->assertSame($expectedHook, (new HookExtractor())->forObject('Foo', ['hooks' => $hooks]));
    }

    /**
     * @return iterable<array{0: ?callable, 1: array<string, callable>}>
     */
    public function forObjectDataProvider(): iterable
    {
        $createHook = fn () => static function () {};

        yield [null, []];
        yield [$hook = $createHook(), ['Foo' => $hook]];
        yield [$hook = $createHook(), ['object' => $hook]];
        yield [$hook = $createHook(), ['Foo' => $hook, 'object' => $createHook()]];
    }

    public function testForKey(): void
    {
        $fooHook = static function (): void {
        };
        $barHook = static function (): void {
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
