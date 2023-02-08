<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
final class AnotherDummyWithFormatterAttributes
{
    public int $id = 1;

    #[Formatter(
        marshal: [self::class, 'uppercase'],
        unmarshal: [self::class, 'lowercase'],
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
