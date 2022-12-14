<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Lexer;

interface LexerInterface
{
    /**
     * @param resource $resource
     *
     * @return \Iterator<string>
     */
    public function tokens(mixed $resource): \Iterator;
}
