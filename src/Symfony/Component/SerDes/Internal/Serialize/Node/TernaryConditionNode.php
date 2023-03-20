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
final class TernaryConditionNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $condition,
        public readonly NodeInterface $onTrue,
        public readonly NodeInterface $onFalse,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->condition)
            ->raw(' ? ')
            ->compile($this->onTrue)
            ->raw(' : ')
            ->compile($this->onFalse);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->condition), $optimizer->optimize($this->onTrue), $optimizer->optimize($this->onFalse));
    }
}
