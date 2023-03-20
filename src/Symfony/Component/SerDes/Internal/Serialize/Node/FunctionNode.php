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
final class FunctionNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly array $parameters,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $parameters = implode(', ', array_map(fn (NodeInterface $v): string => $compiler->subcompile($v), $this->parameters));

        $compiler->raw(sprintf('%s(%s)', $this->name, $parameters));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($this->name, $optimizer->optimize($this->parameters));
    }
}
