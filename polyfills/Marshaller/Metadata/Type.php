<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

use Symfony\Polyfill\Marshaller\Metadata\Type;

final class Type
{
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isCollection = false,
        private readonly ?self $collectionKeyType = null,
        private readonly ?self $collectionValueType = null
    ) {
    }

    public static function fromString(string $type): self
    {
        if ('null' === $type) {
            return new self('null');
        }

        if ($isNullable = '?' === $type[0]) {
            $type = substr($type, 1);
        }

        if (\count(explode('|', $type)) > 1) {
            throw new \LogicException('Not implemented yet (union/intersection).');
        }

        if (in_array($type, ['int', 'string', 'float', 'bool'])) {
            return new self($type, $isNullable);
        }

        if (preg_match('/^(?:array|list)<.+>$/', $type)) {
            throw new \RuntimeException('todo array');
        }

        if (class_exists($type)) {
            return new self('object', $isNullable, $type);
        }

        throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $type));
    }

    public static function fromReflection(\ReflectionNamedType $reflection, \ReflectionClass $declaringClass): self
    {
        $phpTypeOrClass = $reflection->getName();

        if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $reflection));
        }

        if ('array' === $phpTypeOrClass) {
            throw new \RuntimeException('todo array');
        }

        if ($reflection->isBuiltin()) {
            return new Type(name: $phpTypeOrClass, isNullable: $reflection->allowsNull());
        }

        $className = $phpTypeOrClass;

        if ($declaringClass && 'self' === strtolower($className)) {
            $className = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
            $className = $parent->name;
        }

        return new Type(name: 'object', isNullable: $reflection->allowsNull(), className: $className);
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

    public function isNull(): bool
    {
        return 'null' === $this->name;
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }
}
