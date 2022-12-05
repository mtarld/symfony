<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class ArrayAccessNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $array,
        public readonly NodeInterface $key,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf('%s[%s]', $compiler->subcompile($this->array), $compiler->subcompile($this->key)));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
