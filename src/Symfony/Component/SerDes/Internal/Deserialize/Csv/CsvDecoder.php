<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize\Csv;

use Symfony\Component\SerDes\Exception\InvalidResourceException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class CsvDecoder
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<list<mixed>>
     *
     * @throws InvalidResourceException
     */
    public function decode(mixed $resource, array $context): \Iterator
    {
        try {
            /** @var string $content */
            $content = stream_get_contents($resource, \strlen(self::UTF8_BOM));
        } catch (\Throwable) {
            throw new InvalidResourceException($resource);
        }

        if (self::UTF8_BOM !== $content) {
            rewind($resource);
        }

        try {
            while (false !== ($row = fgetcsv(
                $resource,
                separator: $context['csv_separator'] ?? ',',
                enclosure: $context['csv_enclosure'] ?? '"',
                escape: $context['csv_escape_char'] ?? '\\',
            ))) {
                yield $row;
            }
        } catch (\Throwable) {
            throw new InvalidResourceException($resource);
        }
    }
}
