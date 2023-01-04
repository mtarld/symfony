<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;

final class DummyWithFormatterAttributes
{
    #[Formatter(
        marshal: [self::class, 'doubleAndCastToString'],
        unmarshal: [self::class, 'divideAndCastToInt'],
    )]
    public int $id = 1;

    public string $name = 'dummy';

    public static function doubleAndCastToString(int $value): string
    {
        return (string) (2 * $value);
    }

    public static function divideAndCastToInt(string $value): int
    {
        return (int) (((int) $value) / 2);
    }
}
