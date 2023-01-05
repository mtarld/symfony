<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Lexer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class LexerFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): LexerInterface
    {
        return match ($format) {
            'json' => new JsonLexer(),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format.', $format)),
        };
    }
}
