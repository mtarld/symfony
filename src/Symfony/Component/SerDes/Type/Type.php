<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Type;

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Exception\LogicException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class Type implements \Stringable
{
    private string $stringValue;

    /**
     * @param class-string|null    $className
     * @param list<self|UnionType> $genericParameterTypes
     */
    public function __construct(
        private string $name,
        private bool $isNullable = false,
        private ?string $className = null,
        private bool $isGeneric = false,
        private array $genericParameterTypes = [],
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

    public function hasClass(): bool
    {
        return null !== $this->className;
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

        $name = $this->hasClass() ? $this->className() : $this->name();

        if ($this->isGeneric()) {
            $name .= sprintf('<%s>', implode(', ', $this->genericParameterTypes));
        }

        return ($this->isNullable() ? '?' : '').$name;
    }

    public function __toString(): string
    {
        return $this->stringValue;
    }
}
