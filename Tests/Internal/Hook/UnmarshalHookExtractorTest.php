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
}
