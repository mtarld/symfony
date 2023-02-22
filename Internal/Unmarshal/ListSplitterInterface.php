<?php

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Internal\Type\Type;

interface ListSplitterInterface
{
    /**
     * @param resource $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<Boundary>|null
     */
    public function split(mixed $resource, Type $type, array $context): ?\Iterator;
}
