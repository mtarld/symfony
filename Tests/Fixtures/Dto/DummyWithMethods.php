<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
final class DummyWithMethods
{
    public int $id = 1;

    public static function doubleAndCastToString(int $value, array $context): string
    {
        return (string) (2 * $value);
    }

    public static function tooManyParameters(int $value, array $context, bool $extraParameter): string
    {
        return (string) (3 * $value);
    }

    public static function invalidContextType(int $value, bool $context): string
    {
        return (string) (3 * $value);
    }

    public function nonStatic(int $value, array $context): string
    {
        return (string) (3 * $value);
    }

    public static function noArgument(): string
    {
        return 'string';
    }

    public static function void(): void
    {
    }
}
