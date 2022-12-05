<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 *
 * @template T of NodeInterface
 */
final class ExpressionNode implements NodeInterface
{
    /**
     * @param T $node
     */
    public function __construct(
        public readonly NodeInterface $node,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->line($compiler->subcompile($this->node).';');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->node));
    }
}
