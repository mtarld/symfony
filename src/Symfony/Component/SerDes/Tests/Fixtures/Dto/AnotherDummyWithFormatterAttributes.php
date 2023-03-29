<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\DeserializeFormatter;
use Symfony\Component\SerDes\Attribute\SerializeFormatter;

class AnotherDummyWithFormatterAttributes
{
    public int $id = 1;

    #[SerializeFormatter([self::class, 'uppercase'])]
    #[DeserializeFormatter([self::class, 'lowercase'])]
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
