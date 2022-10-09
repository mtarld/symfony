<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

final class Context implements \IteratorAggregate
{
    /**
     * @var array<class-string<OptionInterface>, OptionInterface>
     */
    private readonly array $optionMap;

    public function __construct(OptionInterface ...$options)
    {
        $map = [];
        foreach ($options as $option) {
            $map[get_class($option)] = $option;
        }

        $this->optionMap = $map;
    }

    public function with(OptionInterface ...$options): self
    {
        return new self(...[
            ...array_values($this->optionMap),
            ...$options,
        ]);
    }

    /**
     * @param class-string<OptionInterface> $optionClasses
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
     * @param class-string<OptionInterface> $optionClass
     */
    public function has(string $optionClass): bool
    {
        return isset($this->optionMap[$optionClass]);
    }

    /**
     * @param class-string<OptionInterface>
     *
     * @throws \OutOfBoundsException
     */
    public function get(string $optionClass): OptionInterface
    {
        if (!$this->has($optionClass)) {
            throw new \OutOfBoundsException('TODO');
        }

        return $this->optionMap[$optionClass];
    }

    public function signature(): string
    {
        $signatures = array_map(fn (OptionInterface $o): string => sprintf('%s[%s]', $o::class, $o->signature()), $this->optionMap);

        return implode('|', $signatures);
    }

    /**
     * @return list<OptionInterface>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->optionMap);
    }
}
