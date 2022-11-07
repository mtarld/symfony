<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class Type
{
    /**
     * @param list<self> $collectionKeyTypes
     * @param list<self> $collectionValueTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isCollection = false,
        private readonly array $collectionKeyTypes = [],
        private readonly array $collectionValueTypes = [],
    ) {
    }

    public static function createFromReflection(\ReflectionNamedType $reflection): self
    {
        $name = $reflection->getName();
        $nullable = $reflection->allowsNull();
        $isCollection = 'array' === $reflection->getName();
        $className = null;

        if (!$reflection->isBuiltin()) {
            $name = 'object';
            $className = $reflection->getName();
        }

        return new self($name, $nullable, $className, $isCollection);
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
        return $this->isCollection() && 1 === \count($this->collectionKeyTypes) && 'int' === $this->collectionKeyTypes[0]->name();
    }

    public function isDict(): bool
    {
        return $this->isCollection() && !$this->isList();
    }

    /**
     * @return list<self>
     */
    public function collectionKeyTypes(): array
    {
        return $this->collectionKeyTypes;
    }

    /**
     * @return list<self>
     */
    public function collectionValueTypes(): array
    {
        return $this->collectionValueTypes;
    }
}
