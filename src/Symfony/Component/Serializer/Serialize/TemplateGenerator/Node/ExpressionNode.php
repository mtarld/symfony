<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\TemplateGenerator\Node;

use Symfony\Component\Serializer\Serialize\TemplateGenerator\Compiler;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\NodeInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 *
 * @template T of NodeInterface
 */
final readonly class ExpressionNode implements NodeInterface
{
    /**
     * @param T $node
     */
    public function __construct(
        public NodeInterface $node,
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
