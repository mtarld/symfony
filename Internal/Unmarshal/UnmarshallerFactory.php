<?php

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDecoder;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDictSplitter;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonLexer;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonListSplitter;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

abstract class UnmarshallerFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): Unmarshaller
    {
        return match ($format) {
            'json' => self::createJson(),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function createJson(): Unmarshaller
    {
        $lexer = new JsonLexer();

        return new Unmarshaller(
            hookExtractor: new HookExtractor(),
            reflectionTypeExtractor: new ReflectionTypeExtractor(),
            decoder: new JsonDecoder(),
            listSplitter: new JsonListSplitter($lexer),
            dictSplitter: new JsonDictSplitter($lexer),
        );
    }
}
