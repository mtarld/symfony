<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

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

    public function compile(Compiler $compiler, Optimizer $optimizer): void
    {
        $compiler
            ->compile($this->left)
            ->raw(' = (')
            ->compile($this->right)
            ->raw(')');
    }
}
