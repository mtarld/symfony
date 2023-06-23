<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Encoder;

use Symfony\Component\Serializer\Encoder\CsvEncoder as LegacyCsvEncoder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvEncoder implements EncoderInterface
{
    private static LegacyCsvEncoder|null $legacyCsvEncoder = null;

    public static function encode(mixed $resource, mixed $normalized, array $context): void
    {
        fwrite($resource, (self::$legacyCsvEncoder ??= new LegacyCsvEncoder())->encode($normalized, 'csv', $context));
    }
}
