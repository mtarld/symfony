<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

final class FormatterTest extends TestCase
{
    public function testCanCreateWithValidFunction(): void
    {
        new Formatter(marshal: 'strtoupper');
        new Formatter(unmarshal: 'strtoupper');

        $this->addToAssertionCount(2);
    }

    public function testCanCreateWithValidMethod(): void
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

        new Formatter(marshal: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(marshal: [$objectWithContext::class, 'toUpper']);

        new Formatter(unmarshal: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(unmarshal: [$objectWithContext::class, 'toUpper']);

        $this->addToAssertionCount(4);
    }

    public function testCannotCreateWithInvalidMarshalCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Formatter(marshal: []);
    }

    public function testCannotCreateWithInvalidUnmarshalCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Formatter(unmarshal: []);
    }
}
