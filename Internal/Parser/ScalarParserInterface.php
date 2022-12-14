<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Type\Type;

interface ScalarParserInterface
{
    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     */
    public function parse(\Iterator $tokens, Type $type, array $context): int|float|string|bool|null;
}
