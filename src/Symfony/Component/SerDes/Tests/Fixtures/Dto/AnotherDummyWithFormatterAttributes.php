<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Formatter;

class AnotherDummyWithFormatterAttributes
{
    public int $id = 1;

    #[Formatter(
        onSerialize: [self::class, 'uppercase'],
        onDeserialize: [self::class, 'lowercase'],
    )]
    public string $name = 'dummy';

    public static function uppercase(string $value): string
    {
        return strtoupper($value);
    }

    public static function lowercase(string $value): string
    {
        return strtolower($value);
    }
}
