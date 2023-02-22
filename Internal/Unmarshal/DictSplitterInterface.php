<?php

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Internal\Type\Type;

interface DictSplitterInterface
{
    /**
     * @param resource $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string, Boundary>|null
     */
    public function split(mixed $resource, Type $type, array $context): ?\Iterator;
}
