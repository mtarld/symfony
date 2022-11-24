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

    public function testCannotCreateWithVoidReturnType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Callable of attribute "%s" must be not return "void" nor "never".', Formatter::class));

        $object = new class () {
            public static function void(): void
            {
            }
        };

        new Formatter([$object, 'void']);
    }

    public function testCannotCreateWithNeverReturnType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Callable of attribute "%s" must be not return "void" nor "never".', Formatter::class));

        $object = new class () {
            public static function never(): never
            {
                exit;
            }
        };

        new Formatter([$object, 'never']);
    }

    public function testCannotCreateWithNonStaticMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Callable of attribute "%s" must be static.', Formatter::class));

        $object = new class () {
            public function foo(): string
            {
                return 'bar';
            }
        };

        new Formatter([$object, 'foo']);
    }

    public function testCannotCreateWithoutValidContextArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Callable of attribute "%s" second argument must be an array.', Formatter::class));

        $object = new class () {
            public static function toUpper(string $value, int $context): string
            {
                return strtoupper($value);
            }
        };

        new Formatter([$object, 'toUpper']);
    }
}
