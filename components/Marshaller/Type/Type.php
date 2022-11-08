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
        private readonly ?Types $collectionKeyTypes = null,
        private readonly ?Types $collectionValueTypes = null
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
        return $this->isCollection() && $this->collectionKeyTypes?->isOnly(fn (Type $t): bool => 'int' === $t->name());
    }

    public function isDict(): bool
    {
        return $this->isCollection() && !$this->isList();
    }

    public function collectionKeyTypes(): Types
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection key types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionKeyTypes;
    }

    public function collectionValueTypes(): Types
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection value types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionValueTypes;
    }

    public function __toString(): string
    {
        $diamond = '';
        if ($this->collectionKeyTypes && $this->collectionValueTypes) {
            sprintf('<%s, %s>', $this->collectionKeyTypes, $this->collectionValueTypes);
        }

        $name = $this->name;
        if ($this->isObject()) {
            $name = $this->className;
        }

        return sprintf('%s%s%s', $this->isNullable ? '?' : '', $name, $diamond);
    }
}
