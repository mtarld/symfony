<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

use Symfony\Component\Marshaller\Attribute\Formatter;

final class DummyWithFormatterAttributes
{
    #[Formatter([self::class, 'doubleAndCastToString'])]
    public int $id = 1;

    #[Formatter('strtoupper')]
    public string $name = 'dummy';

    public bool $enabled = true;

    public static function doubleAndCastToString(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
