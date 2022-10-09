<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

final class ValueMetadata
{
    /**
     * @param list<self> $collectionKey
     * @param list<self> $collectionValue
     */
    public function __construct(
        private readonly string $builtinType,
        private readonly bool $isNullable = false,
        private readonly array $collectionKey = [],
        private readonly array $collectionValue = [],
        private readonly ?ClassMetadata $class = null,
    ) {
        if ($this->isObject() && !$class) {
            throw new \LogicException('wrong object');
        }

        if ($this->isArray() && (!$this->collectionKey || !$this->collectionValue)) {
            throw new \LogicException('wrong array');
        }
    }

    public static function createNone(): self
    {
        return new self('none');
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isNone(): bool
    {
        return 'none' === $this->builtinType;
    }

    public function isScalar(): bool
    {
        return $this->isInt() || $this->isString();
    }

    public function isArray(): bool
    {
        return 'array' === $this->builtinType;
    }

    public function isList(): bool
    {
        if (!$this->isArray()) {
            throw new \BadMethodCallException('TODO');
        }

        return $this->collectionKey()->isInt();
    }

    public function isDict(): bool
    {
        return !$this->isList();
    }

    public function collectionKey(): self
    {
        if (!$this->isArray()) {
            throw new \BadMethodCallException('TODO');
        }

        return $this->collectionKey[0];
    }

    public function collectionValue(): self
    {
        if (!$this->isArray()) {
            throw new \BadMethodCallException('TODO');
        }

        return $this->collectionValue[0];
    }

    public function isObject(): bool
    {
        return 'object' === $this->builtinType;
    }

    public function class(): ClassMetadata
    {
        if (!$this->isObject()) {
            throw new \BadMethodCallException('');
        }

        return $this->class;
    }

    public function isString(): bool
    {
        return 'string' === $this->builtinType;
    }

    public function isInt(): bool
    {
        return 'int' === $this->builtinType;
    }
}
