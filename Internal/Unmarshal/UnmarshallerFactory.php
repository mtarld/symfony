<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDecoder;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDictSplitter;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonLexer;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonListSplitter;
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

    public static function create(string $format): Unmarshaller
    {
        return match ($format) {
            'json' => self::json(),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function json(): Unmarshaller
    {
        $lexer = new JsonLexer();

        return new Unmarshaller(
            hookExtractor: new HookExtractor(),
            reflectionTypeExtractor: new ReflectionTypeExtractor(),
            decoder: new JsonDecoder(),
            listSplitter: new JsonListSplitter($lexer),
            dictSplitter: new JsonDictSplitter($lexer),
            instantiator: new Instantiator(),
        );
    }
}
