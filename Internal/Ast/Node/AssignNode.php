<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
