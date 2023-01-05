<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Parser\Json\JsonDictParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonListParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonNullableParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonScalarParser;

final class ParserFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): Parser
    {
        return match ($format) {
            'json' => self::createJson(),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format.', $format)),
        };
    }

    private static function createJson(): Parser
    {
        return new Parser(
            nullableParser: new JsonNullableParser(),
            scalarParser: new JsonScalarParser(),
            listParser: new JsonListParser(),
            dictParser: new JsonDictParser(),
        );
    }
}
