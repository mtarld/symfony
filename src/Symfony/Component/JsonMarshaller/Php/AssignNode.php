<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Php;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class AssignNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $left,
        public PhpNodeInterface $right,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->compile($this->left)
            ->raw(' = ')
            ->compile($this->right);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->left),
            $optimizer->optimize($this->right),
        );
    }
}
