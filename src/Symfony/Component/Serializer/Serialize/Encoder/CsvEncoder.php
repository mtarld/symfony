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
use Symfony\Component\Serializer\Serialize\Configuration\Configuration;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvEncoder implements EncoderInterface
{
    private static LegacyCsvEncoder|null $legacyCsvEncoder = null;

    public static function encode(mixed $resource, mixed $normalized, Configuration $configuration): void
    {
        $csvConfiguration = $configuration->csv();

        $legacyContext = [
            LegacyCsvEncoder::DELIMITER_KEY => $csvConfiguration->delimiter(),
            LegacyCsvEncoder::ENCLOSURE_KEY => $csvConfiguration->enclosure(),
            LegacyCsvEncoder::ESCAPE_CHAR_KEY => $csvConfiguration->escapeChar(),
            LegacyCsvEncoder::END_OF_LINE => $csvConfiguration->endOfLine(),
            LegacyCsvEncoder::ESCAPE_FORMULAS_KEY => $csvConfiguration->escapedFormulas(),
            LegacyCsvEncoder::HEADERS_KEY => $csvConfiguration->headers(),
            LegacyCsvEncoder::KEY_SEPARATOR_KEY => $csvConfiguration->keySeparator(),
            LegacyCsvEncoder::NO_HEADERS_KEY => $csvConfiguration->noHeaders(),
            LegacyCsvEncoder::AS_COLLECTION_KEY => $csvConfiguration->asCollection(),
            LegacyCsvEncoder::OUTPUT_UTF8_BOM_KEY => $csvConfiguration->utf8Bom(),
        ];

        $legacyCsvEncoder = self::$legacyCsvEncoder ??= new LegacyCsvEncoder();

        fwrite($resource, $legacyCsvEncoder->encode($normalized, 'csv', $legacyContext));
    }
}
