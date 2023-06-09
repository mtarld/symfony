<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Deserialize\Json;

use Symfony\Component\Serializer\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonDecoder
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @throws InvalidResourceException
     */
    public function decode(mixed $resource, int $offset, int $length, array $context): mixed
    {
        if (0 === $offset) {
            try {
                /** @var string $content */
                $content = stream_get_contents($resource, \strlen(self::UTF8_BOM));
            } catch (\Throwable) {
                throw new InvalidResourceException($resource);
            }

            if (self::UTF8_BOM === $content) {
                $offset = \strlen(self::UTF8_BOM);
            }
        }

        try {
            /** @var string $content */
            $content = stream_get_contents($resource, $length, $offset);
        } catch (\Throwable) {
            throw new InvalidResourceException($resource);
        }

        try {
            return json_decode($content, associative: true, flags: ($context['json_decode_flags'] ?? 0) | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidResourceException($resource);
        }
    }
}
