<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

interface NullableParserInterface
{
    /**
     * @param \Iterator<string>                  $tokens
     * @param callable(\Iterator<string>): mixed $handle
     * @param array<string, mixed>               $context
     */
    public function parse(\Iterator $tokens, callable $handle, array $context): mixed;
}
