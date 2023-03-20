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
final class ScalarNode implements NodeInterface
{
    public function __construct(
        public readonly mixed $value,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        if (null === $this->value) {
            $compiler->raw('null');

            return;
        }

        if (\is_int($this->value) || \is_float($this->value)) {
            $compiler->raw((string) $this->value);

            return;
        }

        if (\is_bool($this->value)) {
            $compiler->raw($this->value ? 'true' : 'false');

            return;
        }

        if (\is_string($this->value)) {
            $compiler->raw(sprintf('"%s"', addcslashes($this->value, '"\\')));

            return;
        }

        throw new InvalidArgumentException(sprintf('Given value is not a scalar. Got "%s".', get_debug_type($this->value)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
