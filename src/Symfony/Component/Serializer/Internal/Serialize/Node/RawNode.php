<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Serialize\Node;

use Symfony\Component\Serializer\Internal\Serialize\Compiler;
use Symfony\Component\Serializer\Internal\Serialize\NodeInterface;
use Symfony\Component\Serializer\Internal\Serialize\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class RawNode implements NodeInterface
{
    public function __construct(
        public string $source,
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
