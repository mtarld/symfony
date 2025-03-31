<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Mapping;

use Symfony\Component\TypeInfo\Type;

/**
 * Holds stream reading/writing metadata about a given property.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class PropertyMetadata
{
    /**
     * @param list<string|\Closure> $nativeToStreamValueTransformers
     * @param list<string|\Closure> $streamToNativeValueTransformers
     */
    public function __construct(
        private string $name,
        private Type $nativeType,
        private Type $streamType,
        private array $nativeToStreamValueTransformers = [],
        private array $streamToNativeValueTransformers = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        return new self($name, $this->nativeType, $this->streamType, $this->nativeToStreamValueTransformers, $this->streamToNativeValueTransformers);
    }

    public function getNativeType(): Type
    {
        return $this->nativeType;
    }

    public function withNativeType(Type $nativeType): self
    {
        return new self($this->name, $nativeType, $this->streamType, $this->nativeToStreamValueTransformers, $this->streamToNativeValueTransformers);
    }

    public function getStreamType(): Type
    {
        return $this->streamType;
    }

    public function withStreamType(Type $streamType): self
    {
        return new self($this->name, $this->nativeType, $streamType, $this->nativeToStreamValueTransformers, $this->streamToNativeValueTransformers);
    }

    /**
     * @return list<string|\Closure>
     */
    public function getNativeToStreamValueTransformer(): array
    {
        return $this->nativeToStreamValueTransformers;
    }

    /**
     * @param list<string|\Closure> $nativeToStreamValueTransformers
     */
    public function withNativeToStreamValueTransformers(array $nativeToStreamValueTransformers): self
    {
        return new self($this->name, $this->nativeType, $this->streamType, $nativeToStreamValueTransformers, $this->streamToNativeValueTransformers);
    }

    public function withAdditionalNativeToStreamValueTransformer(string|\Closure $nativeToStreamValueTransformer): self
    {
        $nativeToStreamValueTransformers = $this->nativeToStreamValueTransformers;
        $nativeToStreamValueTransformers[] = $nativeToStreamValueTransformer;

        return $this->withNativeToStreamValueTransformers($nativeToStreamValueTransformers);
    }

    /**
     * @return list<string|\Closure>
     */
    public function getStreamToNativeValueTransformers(): array
    {
        return $this->streamToNativeValueTransformers;
    }

    /**
     * @param list<string|\Closure> $streamToNativeValueTransformers
     */
    public function withStreamToNativeValueTransformers(array $streamToNativeValueTransformers): self
    {
        return new self($this->name, $this->nativeType, $this->streamType, $this->nativeToStreamValueTransformers, $streamToNativeValueTransformers);
    }

    public function withAdditionalStreamToNativeValueTransformer(string|\Closure $streamToNativeValueTransformer): self
    {
        $streamToNativeValueTransformers = $this->streamToNativeValueTransformers;
        $streamToNativeValueTransformers[] = $streamToNativeValueTransformer;

        return $this->withStreamToNativeValueTransformers($streamToNativeValueTransformers);
    }
}
