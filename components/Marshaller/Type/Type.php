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
        private readonly bool $nullable,
        private readonly ?string $className = null,
        private readonly array $collectionKeyTypes = [],
        private readonly array $collectionValueTypes = [],
    ) {
    }

    public static function createFromReflection(\ReflectionNamedType $reflection): self
    {
        $name = $reflection->getName();
        $nullable = $reflection->allowsNull();
        $className = null;

        if (!$reflection->isBuiltin()) {
            $name = 'object';
            $className = $reflection->getName();
        }

        return new self($name, $nullable, $className);
    }

    public function isScalar(): bool
    {
        return in_array($this->name, ['int', 'float', 'string', 'bool'], true);
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function isArray(): bool
    {
        return 'array' === $this->name;
    }

    public function isList(): bool
    {
        return $this->isArray() && 1 === \count($this->collectionKeyTypes) && $this->collectionKeyTypes[0]->isInt();
    }

    public function isDict(): bool
    {
        return $this->isArray() && !$this->isList();
    }

    public function className(): string
    {
        if (!$this->isObject()) {
            throw new \RuntimeException('TODO');
        }

        return $this->className;
    }
}
