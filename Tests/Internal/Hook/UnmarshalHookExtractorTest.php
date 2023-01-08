<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Internal\Hook\UnmarshalHookExtractor;

final class UnmarshalHookExtractorTest extends TestCase
{
    public function testExtractFromProperty(): void
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

        $hookExtractor = new UnmarshalHookExtractor();

        $this->assertSame($barHook, $hookExtractor->extractFromKey('unexistingClass', 'foo', $contextWithProperty));
        $this->assertSame($barHook, $hookExtractor->extractFromKey('class', 'unexistingKey', $contextWithProperty));
        $this->assertSame($fooHook, $hookExtractor->extractFromKey('class', 'foo', $contextWithProperty));

        $this->assertNull($hookExtractor->extractFromKey('unexistingClass', 'foo', $contextWithoutProperty));
        $this->assertNull($hookExtractor->extractFromKey('class', 'unexistingKey', $contextWithoutProperty));
    }

    /**
     * @dataProvider hookValidationDataProvider
     */
    public function testHookValidation(?string $expectedExceptionMessage, callable $hook): void
    {
        $context = [
            'hooks' => ['class[key]' => $hook],
        ];

        if (null !== $expectedExceptionMessage) {
            $this->expectException(InvalidArgumentException::class);
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
        yield [null, static function (\ReflectionClass $class, object $object, string $key, callable $value, array $context) {
        }];
        yield ['Hook "class[key]" must have exactly 5 arguments.', static function () {
        }];
        yield ['Hook "class[key]" must have a "ReflectionClass" for first argument.', static function (int $property, object $object, string $key, callable $value, array $context) {
        }];
        yield ['Hook "class[key]" must have an "object" for second argument.', static function (\ReflectionClass $property, int $object, string $key, callable $value, array $context) {
        }];
        yield ['Hook "class[key]" must have a "string" for third argument.', static function (\ReflectionClass $property, object $object, int $key, callable $value, array $context) {
        }];
        yield ['Hook "class[key]" must have a "callable" for fourth argument.', static function (\ReflectionClass $property, object $object, string $key, int $value, array $context) {
        }];
        yield ['Hook "class[key]" must have an "array" for fifth argument.', static function (\ReflectionClass $property, object $object, string $key, callable $value, int $context) {
        }];
    }
}
