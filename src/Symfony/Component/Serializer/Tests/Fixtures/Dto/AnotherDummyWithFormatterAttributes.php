<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;

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
