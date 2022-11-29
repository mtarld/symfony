<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class PropertyNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $object,
        public readonly string $property,
    ) {
    }

    public function compile(Compiler $compiler, Optimizer $optimizer): void
    {
        $compiler
            ->compile($this->object)
            ->raw(sprintf('->%s', $this->property));
    }
}
