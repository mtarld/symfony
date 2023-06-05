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
final readonly class MethodNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $arguments
     */
    public function __construct(
        public NodeInterface $object,
        public string $method,
        public array $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = implode(', ', array_map(fn (NodeInterface $v): string => $compiler->subcompile($v), $this->arguments));

        $compiler
            ->compile($this->object)
            ->raw(sprintf('->%s(%s)', $this->method, $arguments));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->object), $this->method, $optimizer->optimize($this->arguments));
    }
}
