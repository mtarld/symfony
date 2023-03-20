<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDecoder;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDictSplitter;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonLexer;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonListSplitter;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\ValidatingJsonLexer;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class UnmarshallerFactory
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function create(string $format, array $context): Unmarshaller
    {
        return match ($format) {
            'json' => self::json($context['validate_stream'] ?? false),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function json(bool $validate): Unmarshaller
    {
        $lexer = new JsonLexer();
        if ($validate) {
            $lexer = new ValidatingJsonLexer($lexer);
        }

        return new Unmarshaller(
            reflectionTypeExtractor: new ReflectionTypeExtractor(),
            decoder: new JsonDecoder(),
            listSplitter: new JsonListSplitter($lexer),
            dictSplitter: new JsonDictSplitter($lexer),
            instantiator: new Instantiator(),
        );
    }
}
