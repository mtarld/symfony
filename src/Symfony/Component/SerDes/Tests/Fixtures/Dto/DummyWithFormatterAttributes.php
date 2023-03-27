<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Formatter;

class DummyWithFormatterAttributes
{
    #[Formatter(
        onSerialize: [self::class, 'doubleAndCastToString'],
        onDeserialize: [self::class, 'divideAndCastToInt'],
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
