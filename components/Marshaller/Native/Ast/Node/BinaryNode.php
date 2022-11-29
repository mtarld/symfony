<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class BinaryNode implements NodeInterface
{
    private const OPERATORS = [
        '&&',
        '||',
        '===',
        'instanceof',
        '.',
    ];

    public function __construct(
        public readonly string $operator,
        public readonly NodeInterface $left,
        public readonly NodeInterface $right,
    ) {
        if (!in_array($this->operator, self::OPERATORS)) {
            throw new \InvalidArgumentException(sprintf('Invalid "%s" operator.', $this->operator));
        }
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('(')
            ->compile($this->left)
            ->raw(') '.$this->operator.' (')
            ->compile($this->right)
            ->raw(')');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($this->operator, $optimizer->optimize($this->left), $optimizer->optimize($this->right));
    }
}
