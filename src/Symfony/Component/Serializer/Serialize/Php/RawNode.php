<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Php;

use Symfony\Component\Serializer\Serialize\Template\Compiler;
use Symfony\Component\Serializer\Serialize\Template\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
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
