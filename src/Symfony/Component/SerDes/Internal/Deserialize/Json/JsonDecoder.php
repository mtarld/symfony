<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize\Json;

use Symfony\Component\SerDes\Exception\InvalidResourceException;
use Symfony\Component\SerDes\Exception\RuntimeException;
use Symfony\Component\SerDes\Internal\Deserialize\DecoderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonDecoder implements DecoderInterface
{
    public function decode(mixed $resource, int $offset, int $length, array $context): mixed
    {
        try {
            if (false === $content = stream_get_contents($resource, $length, $offset)) {
                throw new \RuntimeException();
            }
        } catch (\Throwable) {
            throw new RuntimeException('Cannot read JSON resource.');
        }

        try {
            return json_decode($content, associative: true, flags: ($context['json_decode_flags'] ?? 0) | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidResourceException($resource);
        }
    }
}
