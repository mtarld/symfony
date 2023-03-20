<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Php;

/**
 * Represents a node in the PHP syntax tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface PhpNodeInterface
{
    public function compile(Compiler $compiler): void;

    public function optimize(Optimizer $optimizer): static;
}
