<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

interface DictParserInterface
{
    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string>
     */
    public function parse(\Iterator $tokens, array $context): \Iterator;
}
