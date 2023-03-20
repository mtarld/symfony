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
