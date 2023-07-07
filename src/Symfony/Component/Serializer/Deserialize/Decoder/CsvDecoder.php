<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Decoder;

use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;
use Symfony\Component\Serializer\Encoder\CsvEncoder as LegacyCsvDecoder;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvDecoder implements DecoderInterface
{
    private readonly LegacyCsvDecoder $legacyCsvDecoder;

    public function __construct()
    {
        $this->legacyCsvDecoder = new LegacyCsvDecoder();
    }

    public function decode(mixed $resource, int $offset, int $length, Configuration $configuration): mixed
    {
        if (false === $content = @stream_get_contents($resource, $length, $offset)) {
            throw new InvalidResourceException($resource);
        }

        $csvConfiguration = $configuration->csv();

        $legacyContext = [
            LegacyCsvDecoder::DELIMITER_KEY => $csvConfiguration->delimiter(),
            LegacyCsvDecoder::ENCLOSURE_KEY => $csvConfiguration->enclosure(),
            LegacyCsvDecoder::ESCAPE_CHAR_KEY => $csvConfiguration->escapeChar(),
            LegacyCsvDecoder::END_OF_LINE => $csvConfiguration->endOfLine(),
            LegacyCsvDecoder::ESCAPE_FORMULAS_KEY => $csvConfiguration->escapedFormulas(),
            LegacyCsvDecoder::HEADERS_KEY => $csvConfiguration->headers(),
            LegacyCsvDecoder::KEY_SEPARATOR_KEY => $csvConfiguration->keySeparator(),
            LegacyCsvDecoder::NO_HEADERS_KEY => $csvConfiguration->noHeaders(),
            LegacyCsvDecoder::AS_COLLECTION_KEY => $csvConfiguration->asCollection(),
            LegacyCsvDecoder::OUTPUT_UTF8_BOM_KEY => $csvConfiguration->utf8Bom(),
        ];

        return $this->legacyCsvDecoder->decode($content, 'csv', $legacyContext);
    }
}
