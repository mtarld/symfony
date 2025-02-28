<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Mapping;

use Symfony\Component\TypeInfo\Type;

/**
 * Holds encoding/decoding metadata about a given property.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class PropertyMetadata
{
    /**
     * @param list<array{native: Type, json: Type}> $types
     * @param array<string, list<string|\Closure>>           $toJsonValueTransformers
     * @param list<string|\Closure>                          $toNativeValueTransformers
     */
    public function __construct(
        private string $name,
        private array $types,
        private array $toJsonValueTransformers = [],
        private array $toNativeValueTransformers = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        return new self($name, $this->types, $this->toJsonValueTransformers, $this->toNativeValueTransformers);
    }

    /**
     * @return list<array{native: Type, json: Type}>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param list<array{native: Type, json: Type}> $types
     */
    public function withTypes(array $types): self
    {
        return new self($this->name, $types, $this->toJsonValueTransformers, $this->toNativeValueTransformers);
    }

    /**
     * @return array<string, string|\Closure>
     */
    public function getToJsonValueTransformers(Type $type): array
    {
        return $this->toJsonValueTransformers[(string) $type] ?? [];
    }

    public function withToJsonValueTransformer(Type $type, string|\Closure $toJsonValueTransformer): self
    {
        $transformers = $this->toJsonValueTransformers[(string) $type] ?? [];
        $transformers[] = $toJsonValueTransformer;

        $transformers = array_values(array_unique($transformers));

        return new self(
            $this->name,
            $this->types,
            [(string) $type => $transformers] + $this->toJsonValueTransformers,
            $this->toNativeValueTransformers,
        );
    }

    /**
     * @return list<string|\Closure>
     */
    public function getToNativeValueTransformers(): array
    {
        return $this->toNativeValueTransformers;
    }

    /**
     * @param list<string|\Closure> $toNativeValueTransformers
     */
    public function withToNativeValueTransformers(array $toNativeValueTransformers): self
    {
        return new self($this->name, $this->types, $this->toJsonValueTransformers, $toNativeValueTransformers);
    }

    public function withAdditionalToNativeValueTransformer(string|\Closure $toNativeValueTransformer): self
    {
        $toNativeValueTransformers = $this->toNativeValueTransformers;

        $toNativeValueTransformers[] = $toNativeValueTransformer;
        $toNativeValueTransformers = array_values(array_unique($toNativeValueTransformers));

        return $this->withToNativeValueTransformers($toNativeValueTransformers);
    }
}
