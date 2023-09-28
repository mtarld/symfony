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
final readonly class ReturnNode implements PhpNodeInterface
{
    public function __construct(
        public ?PhpNodeInterface $node,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->raw(sprintf(
            'return%s',
            null !== $this->node ? ' '.$compiler->subcompile($this->node) : '',
        ));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(null !== $this->node ? $optimizer->optimize($this->node) : null);
    }
}
