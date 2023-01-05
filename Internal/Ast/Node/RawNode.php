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
