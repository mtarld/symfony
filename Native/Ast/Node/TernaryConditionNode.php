<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class TernaryConditionNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $condition,
        public readonly NodeInterface $onTrue,
        public readonly NodeInterface $onFalse,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->condition)
            ->raw(' ? ')
            ->compile($this->onTrue)
            ->raw(' : ')
            ->compile($this->onFalse);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->condition), $optimizer->optimize($this->onTrue), $optimizer->optimize($this->onFalse));
    }
}
