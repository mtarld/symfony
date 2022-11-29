<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class VariableNode implements NodeInterface
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw('$'.$this->name);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
