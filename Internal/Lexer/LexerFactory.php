<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Lexer;

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
