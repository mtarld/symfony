<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Parser\Json\JsonDictParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonListParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonNullableParser;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonScalarParser;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
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
