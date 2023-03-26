<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Attribute\Formatter;

class FormatterTest extends TestCase
{
    public function testCanCreateWithValidFunction()
    {
        new Formatter(onSerialize: 'strtoupper');
        new Formatter(onDeserialize: 'strtoupper');

        $this->addToAssertionCount(2);
    }

    public function testCanCreateWithValidMethod()
    {
        $objectWithoutContext = new class() {
            public static function toUpper(string $value): string
            {
                return strtoupper($value);
            }
        };

        $objectWithContext = new class() {
            public static function toUpper(string $value, array $context): string
            {
                return strtoupper($value);
            }
        };

        new Formatter(onSerialize: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(onSerialize: [$objectWithContext::class, 'toUpper']);

        new Formatter(onDeserialize: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(onDeserialize: [$objectWithContext::class, 'toUpper']);

        $this->addToAssertionCount(4);
    }
}
