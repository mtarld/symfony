<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;

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
