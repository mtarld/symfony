<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

final class UnionType implements \Stringable, \Countable
{
    /**
     * @param list<Type> $ypes
     */
    public function __construct(
        public readonly array $types,
    ) {
    }

    public function isNullable(): bool
    {
        return $this->atLeastOneTypeIs(fn (Type $t): bool => $t->isNull());
    }

    public function __toString(): string
    {
        return implode('|', array_map(fn (Type $t): string => (string) $t));
    }

    public function count(): int
    {
        return \count($this->types);
    }

    private function everyTypeIs(callable $callable): bool
    {
        foreach ($this->types as $type) {
            if (!$callable($type)) {
                return false;
            }
        }

        return true;
    }

    private function atLeastOneTypeIs(callable $callable): bool
    {
        foreach ($this->types as $type) {
            if ($callable($type)) {
                return true;
            }
        }

        return false;
    }
}
