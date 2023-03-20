<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Decode;

use Symfony\Component\Encoder\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class Decoder
{
    public static function decode(mixed $resource, int $offset, int $length, int $flags = 0): mixed
    {
        if (false === $content = @stream_get_contents($resource, $length, $offset)) {
            throw new InvalidResourceException($resource);
        }

        try {
            return json_decode($content, associative: true, flags: $flags | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidResourceException($resource);
        }
    }
}
