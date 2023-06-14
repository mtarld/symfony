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
final readonly class ArgumentsNode implements NodeInterface
{
    /**
     * @param array<string, ?string> $arguments
     */
    public function __construct(
        public array $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $argumentSources = [];
        foreach ($this->arguments as $name => $type) {
            $type = $type ? $type.' ' : '';
            $name = $compiler->subcompile(new VariableNode($name));

            $argumentSources[] = $type.$name;
        }

        $compiler->raw(implode(', ', $argumentSources));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
