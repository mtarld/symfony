<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class AssignNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $left,
        public readonly NodeInterface $right,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->left)
            ->raw(' = ')
            ->compile($this->right);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->left), $optimizer->optimize($this->right));
    }
}
