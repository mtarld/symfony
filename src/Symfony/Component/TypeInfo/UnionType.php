<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final class UnionType extends Type
{
    use CompositeTypeTrait;

    /**
     * @var list<Type>
     */
    private array $types;

    private Type|null $nullableType;

    public function __construct(Type ...$types)
    {
        usort($types, fn (Type $a, Type $b): int => (string) $a <=> (string) $b);
        $this->types = array_values(array_unique($types));

        $nullableType = null;
        if (\count($this->types) === 2 && in_array('null', array_map(fn (Type $t): string => $t->getBuiltinType(), $this->types), true)) {
            $nullableType = 'null' === $this->types[0]->getBuiltinType() ? $this->types[1] : $this->types[0];
            $stringRepresentation = '?'.(string) $nullableType;
        } else {
            $stringRepresentation = '';
            $glue = '';
            foreach ($this->types as $t) {
                $stringRepresentation .= $glue.((string) $t);
                $glue = '|';
            }
        }

        $this->nullableType = $nullableType;
        $this->stringRepresentation = $stringRepresentation;
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getBuiltinType(): string
    {
        if ($this->nullableType) {
            return $this->nullableType->getBuiltinType();
        }

        throw $this->createUnhandledException(__METHOD__);
    }

    public function isNullable(): bool
    {
        return $this->atLeastOneTypeIs(fn (Type $t): bool => $t->isNullable());
    }

    public function isScalar(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isScalar();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isScalar());
    }

    public function isObject(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isObject();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isObject());
    }

    public function getClassName(): string
    {
        if ($this->nullableType) {
            return $this->nullableType->getClassName();
        }

        throw $this->createUnhandledException(__METHOD__);
    }

    public function isEnum(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isEnum();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isEnum());
    }

    public function isBackedEnum(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isBackedEnum();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isBackedEnum());
    }

    public function getEnumBackingType(): self
    {
        if ($this->nullableType) {
            return $this->nullableType->getEnumBackingType();
        }

        throw $this->createUnhandledException(__METHOD__);
    }

    public function isCollection(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isCollection();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isCollection());
    }

    public function getCollectionKeyType(): self
    {
        if ($this->nullableType) {
            return $this->nullableType->getCollectionKeyType();
        }

        throw $this->createUnhandledException(__METHOD__);
    }

    public function getCollectionValueType(): self
    {
        if ($this->nullableType) {
            return $this->nullableType->getCollectionValueType();
        }

        throw $this->createUnhandledException(__METHOD__);
    }

    public function isList(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isList();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isList());
    }

    public function isDict(): bool
    {
        if ($this->nullableType) {
            return $this->nullableType->isDict();
        }

        return $this->everyTypeIs(fn (Type $t): bool => $t->isDict());
    }

    public function __toString(): string
    {
        return $this->stringRepresentation;
    }
}
