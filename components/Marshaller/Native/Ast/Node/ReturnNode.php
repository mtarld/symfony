<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;

/**
 * @internal
 */
final class ReturnNode implements NodeInterface
{
    public function __construct(
        public readonly NodeInterface $node,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->line(sprintf('return %s;', $compiler->subcompile($this->node)));
    }
}
