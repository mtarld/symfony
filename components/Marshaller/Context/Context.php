<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

use Symfony\Component\Marshaller\Context\Option\NullableDataOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

final class Context implements \IteratorAggregate
{
    /**
     * @var array<class-string, object>
     */
    private readonly array $optionMap;

    public function __construct(object ...$options)
    {
        $map = [];
        foreach ($options as $option) {
            $map[get_class($option)] = $option;
        }

        if (isset($map[NullableDataOption::class], $map[TypeOption::class])) {
            throw new \InvalidArgumentException(sprintf('Cannot use both "%s" and "%s" options.', NullableDataOption::class, TypeOption::class));
        }

        $this->optionMap = $map;
    }

    public function with(object ...$options): self
    {
        return new self(...[
            ...array_values($this->optionMap),
            ...$options,
        ]);
    }

    /**
     * @param class-string $optionClasses
     */
    public function without(string ...$optionClasses): self
    {
        $clone = clone $this;
        foreach ($optionClasses as $optionClass) {
            unset($clone->optionMap[$optionClass]);
        }

        return $clone;
    }

    /**
     * @param class-string $optionClass
     */
    public function get(string $optionClass): ?object
    {
        return $this->optionMap[$optionClass] ?? null;
    }

    /**
     * @return list<object>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->optionMap);
    }
}
