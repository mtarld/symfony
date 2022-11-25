<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

final class DummyWithMethods
{
    public int $id = 1;

    public static function doubleAndCastToString(int $value, array $context): string
    {
        return (string) (2 * $value);
    }

    public function tripleAndCastToString(int $value, array $context): string
    {
        return (string) (3 * $value);
    }

    public static function void(): void
    {
    }
}
