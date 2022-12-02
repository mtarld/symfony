<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

/**
 * @implements \IteratorAggregate<object>
 */
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
        $optionMap = $this->optionMap;

        foreach ($optionClasses as $optionClass) {
            unset($optionMap[$optionClass]);
        }

        return new self(...array_values($optionMap));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $optionClass
     *
     * @return T|null
     */
    public function get(string $optionClass): ?object
    {
        return $this->optionMap[$optionClass] ?? null;
    }

    /**
     * @return \Traversable<object>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->optionMap);
    }
}
