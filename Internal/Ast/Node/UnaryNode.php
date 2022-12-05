<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class UnaryNode implements NodeInterface
{
    private const OPERATORS = [
        '!',
    ];

    public function __construct(
        public readonly string $operator,
        public readonly NodeInterface $node,
    ) {
        if (!in_array($this->operator, self::OPERATORS)) {
            throw new \InvalidArgumentException(sprintf('Invalid "%s" operator.', $this->operator));
        }
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw($this->operator)
            ->compile($this->node);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($this->operator, $optimizer->optimize($this->node));
    }
}
