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
