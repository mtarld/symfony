<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Serialize\Node;

use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class ReturnNode implements NodeInterface
{
    public function __construct(
        public NodeInterface $node,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf('return %s', $compiler->subcompile($this->node)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->node));
    }
}
