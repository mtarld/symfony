<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
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

        throw new \InvalidArgumentException(sprintf('Given value is not a scalar. Got "%s"', get_debug_type($this->value)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
