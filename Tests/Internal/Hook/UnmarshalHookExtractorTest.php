<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Hook\UnmarshalHookExtractor;

final class UnmarshalHookExtractorTest extends TestCase
{
    public function testExtractFromProperty(): void
    {
        $fooHook = static function (\ReflectionClass $class, object $object, callable $value, array $context): void {
        };

        $context = [
            'hooks' => [
                self::class => ['foo' => $fooHook],
            ],
        ];

        $hookExtractor = new UnmarshalHookExtractor();

        $this->assertNull($hookExtractor->extractFromKey('unexistingClass', 'foo', $context));
        $this->assertNull($hookExtractor->extractFromKey(self::class, 'unexistingKey', $context));
        $this->assertSame($fooHook, $hookExtractor->extractFromKey(self::class, 'foo', $context));
    }

    /**
     * @dataProvider hookValidationDataProvider
     */
    public function testHookValidation(?string $expectedExceptionMessage, callable $hook): void
    {
        $context = [
            'hooks' => [
                'class' => ['key' => $hook],
            ],
        ];

        if (null !== $expectedExceptionMessage) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        (new UnmarshalHookExtractor())->extractFromKey('class', 'key', $context);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: callable}>
     */
    public function hookValidationDataProvider(): iterable
    {
        yield [null, static function (\ReflectionClass $class, object $object, callable $value, array $context) {
        }];
        yield ['Hook "key" of "class" must have exactly 4 arguments.', static function () {
        }];
        yield ['Hook "key" of "class" must have a "ReflectionClass" for first argument.', static function (int $property, object $object, callable $value, array $context) {
        }];
        yield ['Hook "key" of "class" must have an "object" for second argument.', static function (\ReflectionClass $property, int $object, callable $value, array $context) {
        }];
        yield ['Hook "key" of "class" must have a "callable" for third argument.', static function (\ReflectionClass $property, object $object, int $value, array $context) {
        }];
        yield ['Hook "key" of "class" must have an "array" for fourth argument.', static function (\ReflectionClass $property, object $object, callable $value, int $context) {
        }];
    }
}
