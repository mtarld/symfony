<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

use Symfony\Component\JsonMarshaller\Attribute\UnmarshalFormatter;
use Symfony\Component\JsonMarshaller\Attribute\MarshalFormatter;

class DummyWithFormatterAttributes
{
    #[MarshalFormatter([self::class, 'doubleAndCastToString'])]
    #[UnmarshalFormatter([self::class, 'divideAndCastToInt'])]
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

    public static function doubleAndCastToStringWithConfig(int $value, array $config): string
    {
        return (string) (2 * $config['scale'] * $value);
    }

    public static function divideAndCastToIntWithConfig(string $value, array $config): int
    {
        return (int) (((int) $value) / (2 * $config['scale']));
    }
}
