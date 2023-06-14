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
 */
final readonly class VariableNode implements NodeInterface
{
    public function __construct(
        public string $name,
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
