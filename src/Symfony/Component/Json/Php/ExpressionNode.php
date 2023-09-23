<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Php;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @template T of PhpNodeInterface
 */
final readonly class ExpressionNode implements PhpNodeInterface
{
    /**
     * @param T $node
     */
    public function __construct(
        public PhpNodeInterface $node,
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
