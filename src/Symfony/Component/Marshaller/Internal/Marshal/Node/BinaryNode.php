<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal\Node;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class BinaryNode implements NodeInterface
{
    private const OPERATORS = [
        '&&',
        '||',
        '===',
        'instanceof',
        '??',
    ];

    public function __construct(
        public readonly string $operator,
        public readonly NodeInterface $left,
        public readonly NodeInterface $right,
    ) {
        if (!\in_array($this->operator, self::OPERATORS)) {
            throw new InvalidArgumentException(sprintf('Invalid "%s" operator.', $this->operator));
        }
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->left)
            ->raw(' '.$this->operator.' ')
            ->compile($this->right);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($this->operator, $optimizer->optimize($this->left), $optimizer->optimize($this->right));
    }
}
