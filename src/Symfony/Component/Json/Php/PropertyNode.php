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
final readonly class PropertyNode implements PhpNodeInterface
{
    public function __construct(
        public PhpNodeInterface $object,
        public string $property,
        public bool $static = false,
        public bool $nullSafe = false,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $operator = match (true) {
            $this->static => '::$',
            $this->nullSafe => '?->',
            default => '->',
        };

        $compiler
            ->compile($this->object)
            ->raw(sprintf('%s%s', $operator, $this->property));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->object),
            $this->property,
            $this->static,
            $this->nullSafe,
        );
    }
}
