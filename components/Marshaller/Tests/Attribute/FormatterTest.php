<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Formatter;

final class FormatterTest extends TestCase
{
    public function testCanCreateWithValidFunction(): void
    {
        new Formatter('strtoupper');

        $this->addToAssertionCount(1);
    }

    public function testCanCreateWithValidMethod(): void
    {
        $objectWithoutContext = new class () {
            public static function toUpper(string $value): string
            {
                return strtoupper($value);
            }
        };

        $objectWithContext = new class () {
            public static function toUpper(string $value, array $context): string
            {
                return strtoupper($value);
            }
        };

        new Formatter([$objectWithoutContext::class, 'toUpper']);
        new Formatter([$objectWithContext::class, 'toUpper']);

        $this->addToAssertionCount(2);
    }

    public function testCannotCreateWithInvalidCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Parameter "$callable" of attribute "%s" must be a valid callable.', Formatter::class));

        new Formatter([]);
    }
}
