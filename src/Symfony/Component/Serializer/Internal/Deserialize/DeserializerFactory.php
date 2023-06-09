<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Deserialize;

use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Internal\Deserialize\Csv\CsvDecoder;
use Symfony\Component\Serializer\Internal\Deserialize\Csv\CsvDeserializer;
use Symfony\Component\Serializer\Internal\Deserialize\Json\JsonDecoder;
use Symfony\Component\Serializer\Internal\Deserialize\Json\JsonDictSplitter;
use Symfony\Component\Serializer\Internal\Deserialize\Json\JsonEagerDeserializer;
use Symfony\Component\Serializer\Internal\Deserialize\Json\JsonLazyDeserializer;
use Symfony\Component\Serializer\Internal\Deserialize\Json\JsonListSplitter;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class DeserializerFactory
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function create(string $format, array $context): Deserializer
    {
        $lazyReading = $context['lazy_reading'] ?? false;

        return match ($format) {
            'json' => self::json($lazyReading),
            'csv' => self::csv($lazyReading),
            default => throw new UnsupportedException(sprintf('"%s" format is not supported.', $format)),
        };
    }

    private static function json(bool $lazy): Deserializer
    {
        if ($lazy) {
            return new JsonLazyDeserializer(
                reflectionTypeExtractor: new ReflectionTypeExtractor(),
                decoder: new JsonDecoder(),
                listSplitter: new JsonListSplitter(),
                dictSplitter: new JsonDictSplitter(),
            );
        }

        return new JsonEagerDeserializer(new ReflectionTypeExtractor(), new JsonDecoder());
    }

    private static function csv(bool $lazy): Deserializer
    {
        return new CsvDeserializer(new ReflectionTypeExtractor(), new CsvDecoder(), $lazy);
    }
}
