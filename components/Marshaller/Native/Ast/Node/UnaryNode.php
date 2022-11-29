<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

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

    public function compile(Compiler $compiler, Optimizer $optimizer): void
    {
        $compiler
            ->raw('('.$this->operator)
            ->compile($this->node)
            ->raw(')');
    }
}
