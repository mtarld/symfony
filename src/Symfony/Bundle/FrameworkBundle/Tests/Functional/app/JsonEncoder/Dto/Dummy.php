<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonEncoder\Dto;

use Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonEncoder\NameFormatter;
use Symfony\Component\JsonEncoder\Attribute\DecodeFormatter;
use Symfony\Component\JsonEncoder\Attribute\EncodedName;
use Symfony\Component\JsonEncoder\Attribute\EncodeFormatter;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class Dummy
{
    #[EncodedName('@name')]
    #[EncodeFormatter([self::class, 'uppercase'])]
    #[DecodeFormatter([self::class, 'lowercase'])]
    public string $name = 'dummy';

    public static function uppercase(string $data, NameFormatter $formatter): string
    {
        return $formatter->uppercase($data);
    }

    public static function lowercase(string $data, NameFormatter $formatter): string
    {
        return $formatter->lowercase($data);
    }
}
