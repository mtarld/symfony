<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Lexer;

interface LexerInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Generator<string>
     */
    public function tokens(mixed $resource, array $context): \Generator;
}
