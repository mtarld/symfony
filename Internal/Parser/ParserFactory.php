<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Lexer\JsonLexer;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonListParser;

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
            'json' => new Parser(
                new JsonLexer(),
                new JsonListParser(),
            ),
            default => throw new UnsupportedFormatException($format),
        };
    }
}
