<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Template;

use Symfony\Component\JsonMarshaller\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class Decoder
{
    // TODO
    public static function decode(mixed $resource, int $offset, int $length, array $config): mixed
    {
        if ('' === $content = @stream_get_contents($resource, $length, $offset)) {
            throw new InvalidResourceException($resource);
        }

        try {
            return json_decode($content, associative: true, flags: ($config['json_decode_flags'] ?? 0) | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidResourceException($resource);
        }
    }
}
