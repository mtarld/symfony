<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class RawNode implements NodeInterface
{
    public function __construct(
        public readonly string $source,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw($this->source);
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
