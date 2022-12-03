<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 *
 * @template T of list<NodeInterface>
 */
final class FunctionNode implements NodeInterface
{
    /**
     * @param T $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly array $parameters
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
