<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal;

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Exception\LogicException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Type implements \Stringable
{
    private readonly string $stringValue;

    /**
     * @param class-string|null    $className
     * @param list<self|UnionType> $genericParameterTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isGeneric = false,
        private readonly array $genericParameterTypes = [],
    ) {
        if ($this->isGeneric && !$this->genericParameterTypes) {
            throw new InvalidArgumentException(sprintf('Missing generic parameter types of "%s" type.', $this->name));
        }

        $this->stringValue = $this->computeStringValue();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        if (!$this->isObject() && !$this->isEnum()) {
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an object nor an enum.', $this->name));
        }

        if (null === $this->className) {
            throw new LogicException(sprintf('No class has been defined for "%s".', $this->name));
        }

        return $this->className;
    }

    /**
     * @return list<self|UnionType>
     */
    public function genericParameterTypes(): array
    {
        return $this->genericParameterTypes;
    }

    public function isScalar(): bool
    {
        return \in_array($this->name, ['int', 'float', 'string', 'bool'], true);
    }

    public function isNull(): bool
    {
        return 'null' === $this->name;
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function isEnum(): bool
    {
        return 'enum' === $this->name;
    }

    public function isGeneric(): bool
    {
        return $this->isGeneric;
    }

    public function isCollection(): bool
    {
        return \in_array($this->name, ['array', 'iterable'], true);
    }

    public function isIterable(): bool
    {
        return 'iterable' === $this->name;
    }

    public function isList(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'int' === $collectionKeyType->name();
    }

    public function isDict(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'string' === $collectionKeyType->name();
    }

    public function collectionKeyType(): self|UnionType
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[0] ?? new self('mixed');
    }

    public function collectionValueType(): self|UnionType
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[1] ?? new self('mixed');
    }

    private function computeStringValue(): string
    {
        if ($this->isNull()) {
            return 'null';
        }

        $nullablePrefix = $this->isNullable() ? '?' : '';

        try {
            $name = $this->className();
        } catch (LogicException) {
            $name = $this->name();
        }

        if ($this->isGeneric()) {
            $name .= sprintf('<%s>', implode(', ', $this->genericParameterTypes));
        }

        return $nullablePrefix.$name;
    }

    public function __toString(): string
    {
        return $this->stringValue;
    }
}
