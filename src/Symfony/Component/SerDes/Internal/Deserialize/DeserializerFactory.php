<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize;

use Symfony\Component\SerDes\Exception\UnsupportedFormatException;
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonDecoder;
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonDictSplitter;
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonLexer;
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonListSplitter;
use Symfony\Component\SerDes\Internal\Deserialize\Json\ValidatingJsonLexer;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

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
        $validateStream = $lazyReading && ($context['validate_stream'] ?? false);

        return match ($format) {
            'json' => self::json($lazyReading, $validateStream),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function json(bool $lazy, bool $validate): Deserializer
    {
        if ($lazy) {
            $lexer = new JsonLexer();
            if ($validate) {
                $lexer = new ValidatingJsonLexer($lexer);
            }

            return new LazyDeserializer(
                reflectionTypeExtractor: new ReflectionTypeExtractor(),
                decoder: new JsonDecoder(),
                listSplitter: new JsonListSplitter($lexer),
                dictSplitter: new JsonDictSplitter($lexer),
            );
        }

        return new EagerDeserializer(new ReflectionTypeExtractor());
    }
}
