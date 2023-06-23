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

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvDecoder implements DecoderInterface
{
    private readonly CsvEncoder $decoder;

    public function __construct() 
    {
        $this->decoder = new CsvEncoder();
    }

    public function decode(mixed $resource, int $offset, int $length, array $context): mixed
    {
        try {
            /** @var string $content */
            $content = stream_get_contents($resource, $length, $offset);
        } catch (\Throwable) {
            throw new InvalidResourceException($resource);
        }

        return $this->decoder->decode($content, 'csv', $context);
    }
}
