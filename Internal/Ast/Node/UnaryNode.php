<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
        if (!\in_array($this->operator, self::OPERATORS)) {
            throw new InvalidArgumentException(sprintf('Invalid "%s" operator.', $this->operator));
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
