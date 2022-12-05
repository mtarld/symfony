<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

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

    public function compile(Compiler $compiler): void
    {
        if ([] === $this->lines) {
            return;
        }

        $compiler->line('/**');
        foreach ($this->lines as $line) {
            if ('' === $line) {
                $compiler->line(' *');

                continue;
            }

            $compiler->line(' * '.$line);
        }
        $compiler->line(' */');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return $this;
    }
}
