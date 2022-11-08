<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class Types implements \Stringable
{
    /**
     * @param list<Type> $types
     */
    public function __construct(
        public readonly array $types,
    ) {
        if ([] === $types) {
            throw new \InvalidArgumentException(sprintf('"%s" must contains at least one "%s"', self::class, Type::class));
        }
    }

    public function isNullable(): bool
    {
        return $this->has(fn (Type $t): bool => $t->isNullable());
    }

    public function isOnlyScalar(): bool
    {
        return $this->isOnly(fn (Type $t): bool => $t->isScalar());
    }

    public function isOnlyObject(): bool
    {
        return $this->isOnly(fn (Type $t): bool => $t->isObject());
    }

    public function isSameClass(): bool
    {
        $className = $this->types[0]->className();

        return $this->isOnly(fn (Type $t): bool => $className === $t->className());
    }

    public function isOnlyCollection(): bool
    {
        return $this->isOnly(fn (Type $t): bool => $t->isCollection());
    }

    public function isOnlyDict(): bool
    {
        return $this->isOnly(fn (Type $t): bool => $t->isDict());
    }

    public function isOnlyList(): bool
    {
        return $this->isOnly(fn (Type $t): bool => $t->isList());
    }

    public function isOnly(callable $condition): bool
    {
        return \count($this->types) === \count(array_filter($this->types, $condition));
    }

    public function has(callable $condition): bool
    {
        foreach ($this->types as $type) {
            if ($condition($type)) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        // TODO handle intersection types?
        return implode('|', $this->types);
    }
}
