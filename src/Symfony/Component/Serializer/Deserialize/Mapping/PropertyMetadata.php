<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;


/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PropertyMetadata
{
    /**
     * @param callable(callable(Type): mixed): mixed $valueProvider
     */
    public function __construct(
        private string $name,
        private mixed $valueProvider
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function valueProvider(): callable
    {
        return $this->valueProvider;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * @param callable(callable(Type): mixed): mixed $valueProvider
     */
    public function withValueProvider(callable $valueProvider): self
    {
        $clone = clone $this;
        $clone->valueProvider = $valueProvider;

        return $clone;
    }

    /**
     * @param callable(mixed): mixed $wrapper
     */
    public function withValueProviderWrapper(callable $wrapper): self
    {
        $clone = clone $this;
        $clone->valueProvider = fn (callable $valueProvider) => $wrapper(($this->valueProvider)($valueProvider));

        return $clone;
    }
}
