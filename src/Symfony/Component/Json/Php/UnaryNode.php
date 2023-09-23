<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Php;

use Symfony\Component\Encoder\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class UnaryNode implements PhpNodeInterface
{
    private const OPERATORS = [
        '!',
    ];

    public function __construct(
        public string $operator,
        public PhpNodeInterface $node,
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
        return new self(
            $this->operator,
            $optimizer->optimize($this->node),
        );
    }
}
