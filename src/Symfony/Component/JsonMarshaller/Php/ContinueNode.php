<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Php;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class ContinueNode implements PhpNodeInterface
{
    public function compile(Compiler $compiler): void
    {
        $compiler->raw('continue');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self();
    }
}
