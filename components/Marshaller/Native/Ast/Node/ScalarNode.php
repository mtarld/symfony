<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;

/**
 * @internal
 */
final class ScalarNode implements NodeInterface
{
    public function __construct(
        public readonly mixed $value,
        public readonly bool $escaped = true,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->repr($this->value);
    }
}
