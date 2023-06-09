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
