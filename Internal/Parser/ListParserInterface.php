<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

interface ListParserInterface
{
    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return \Iterator<null>
     */
    public function parse(\Iterator $tokens, array $context): \Iterator;
}
