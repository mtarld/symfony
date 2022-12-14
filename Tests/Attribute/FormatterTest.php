<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Formatter;

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

        new Formatter(marshal: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(marshal: [$objectWithContext::class, 'toUpper']);

        new Formatter(unmarshal: [$objectWithoutContext::class, 'toUpper']);
        new Formatter(unmarshal: [$objectWithContext::class, 'toUpper']);

        $this->addToAssertionCount(4);
    }

    public function testCannotCreateWithInvalidMarshalCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Parameter "$marshal" of attribute "%s" must be a valid callable.', Formatter::class));

        new Formatter(marshal: []);
    }

    public function testCannotCreateWithInvalidUnmarshalCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Parameter "$unmarshal" of attribute "%s" must be a valid callable.', Formatter::class));

        new Formatter(unmarshal: []);
    }
}
