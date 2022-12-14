<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

interface ObjectParserInterface
{
    /**
     * @param \Iterator<string>                         $tokens
     * @param callable(string, \Iterator<string>): void $setProperty
     * @param array<string, mixed>                      $context
     */
    public function parse(\Iterator $tokens, callable $setProperty, array $context): void;
}
