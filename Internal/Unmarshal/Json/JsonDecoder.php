<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\RuntimeException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Boundary;
use Symfony\Component\Marshaller\Internal\Unmarshal\DecoderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonDecoder implements DecoderInterface
{
    public function decode(mixed $resource, Boundary $boundary, array $context): mixed
    {
        if (false === $content = stream_get_contents($resource, $boundary->length, $boundary->offset)) {
            throw new RuntimeException('Cannot read JSON resource.');
        }

        try {
            $data = json_decode($content, associative: true, flags: $context['json_decode_flags'] ?? 0);
        } catch (\JsonException $e) {
            throw new InvalidResourceException($resource);
        }

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidResourceException($resource);
        }

        return $data;
    }
}

