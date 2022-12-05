<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class ArgumentsNode implements NodeInterface
{
    /**
     * @param array<string, ?string> $arguments
     */
    public function __construct(
        public readonly array $arguments,
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
