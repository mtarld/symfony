<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
