<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

interface ListParserInterface
{
    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return list<mixed>
     */
    public function parse(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): array;

    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     *
     * @return iterable<mixed>
     */
    public function parseIterable(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): iterable;
}
