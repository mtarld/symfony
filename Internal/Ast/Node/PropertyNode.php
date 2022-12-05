<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

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

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->object)
            ->raw(sprintf('->%s', $this->property));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->object), $this->property);
    }
}
