<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class PhpDocNode implements NodeInterface
{
    /**
     * @param list<string> $lines
     */
    public function __construct(
        public readonly array $lines,
    ) {
    }

    public function compile(Compiler $compiler, Optimizer $optimizer): void
    {
        if ([] === $this->lines) {
            return;
        }

        $compiler->line('/**');
        foreach ($this->lines as $line) {
            $compiler->line(' * '.$line);
        }
        $compiler->line(' */');
    }
}
