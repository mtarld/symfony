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
final readonly class NewNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $arguments
     */
    public function __construct(
        public string $class,
        public array $arguments,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = implode(', ', array_map(fn (NodeInterface $v): string => $compiler->subcompile($v), $this->arguments));

        $compiler->raw(sprintf('new %s(%s)', $this->class, $arguments));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($this->class, $optimizer->optimize($this->arguments));
    }
}
