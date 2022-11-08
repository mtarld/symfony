<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class Type implements \Stringable
{
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isCollection = false,
        private readonly ?Type $collectionKeyType = null,
        private readonly ?Type $collectionValueType = null
    ) {
        if ($this->isObject() && null === $this->className) {
            throw new \InvalidArgumentException('Cannot specify an object without a class name.');
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function className(): string
    {
        if (!$this->isObject()) {
            throw new \RuntimeException('Cannot get class on "%s" type as it\'s not an object', $this->name);
        }

        return $this->className;
    }

    public function isScalar(): bool
    {
        return in_array($this->name, ['int', 'float', 'string', 'bool'], true);
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function isList(): bool
    {
        return $this->isCollection() && 'int' === $this->collectionKeyType->name();
    }

    public function isDict(): bool
    {
        return $this->isCollection() && !$this->isList();
    }

    public function collectionKeyType(): Type
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection key types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionKeyType;
    }

    public function collectionValueType(): Type
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection value types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionValueType;
    }

    public function __toString(): string
    {
        $diamond = '';
        if ($this->collectionKeyType && $this->collectionValueType) {
            sprintf('<%s, %s>', $this->collectionKeyType, $this->collectionValueType);
        }

        $name = $this->name;
        if ($this->isObject()) {
            $name = $this->className;
        }

        return sprintf('%s%s%s', $this->isNullable ? '?' : '', $name, $diamond);
    }
}
