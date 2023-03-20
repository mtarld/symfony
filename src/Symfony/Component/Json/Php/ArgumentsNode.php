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
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class ArgumentsNode implements PhpNodeInterface
{
    /**
     * @param array<PhpNodeInterface> $arguments
     */
    public function __construct(
        public array $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $glue = '';
        foreach ($this->arguments as $argument) {
            $compiler->raw($glue);
            $compiler->raw($compiler->subcompile($argument));
            $glue = ', ';
        }
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->arguments));
    }
}
