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

use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
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
     * @param list<array{native: Type, stream: Type, transformers: list<string|\Closure>}> $nativeToStreamTypeMetadata
     * @param list<array{native: Type, stream: Type, transformers: list<string|\Closure>}> $streamToNativeTypeMetadata
     */
    public function __construct(
        private string $name,
        private array $nativeToStreamTypeMetadata = [],
        private array $streamToNativeTypeMetadata = [],
    ) {
        $nativeTypes = array_column($nativeToStreamTypeMetadata, 'native');
        if (count(array_unique($nativeTypes)) !== count($nativeTypes)) {
            throw new InvalidArgumentException('TODO');
        }

        $streamTypes = array_column($streamToNativeTypeMetadata, 'native');
        if (count(array_unique($streamTypes)) !== count($streamTypes)) {
            throw new InvalidArgumentException('TODO');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        return new self($name, $this->nativeToStreamTypeMetadata, $this->streamToNativeTypeMetadata);
    }

    /**
     * @return list<array{native: Type, stream: Type, transformers: list<string|\Closure>}>
     */
    public function getNativeToStreamTypeMetadata(): array
    {
        return $this->nativeToStreamTypeMetadata;
    }

    /**
     * @param list<array{native: Type, stream: Type, transformers: list<string|\Closure>}> $metadata
     */
    public function withNativeToStreamTypeMetadata(array $metadata): self
    {
        return new self($this->name, $metadata, $this->streamToNativeTypeMetadata);
    }

    public function withNativeToStreamType(Type $nativeType, Type $streamType, string|\Closure $transformer): self
    {
        $metadata = $this->nativeToStreamTypeMetadata;

        foreach ($metadata as &$m) {
            if ($nativeType != $m['native']) {
                continue;
            }

            $m['stream'] = $streamType;
            $m['transformers'][] = $transformer;

            return new self($this->name, $metadata, $this->streamToNativeTypeMetadata);
        }

        $metadata[] = ['native' => $nativeType, 'stream' => $streamType, 'transformers' => [$transformer]];

        return new self($this->name, $metadata, $this->streamToNativeTypeMetadata);
    }

    /**
     * @return list<array{native: Type, stream: Type, transformers: list<string|\Closure>}>
     */
    public function getStreamToNativeTypeMetadata(): array
    {
        return $this->streamToNativeTypeMetadata;
    }

    /**
     * @param list<array{native: Type, stream: Type, transformers: list<string|\Closure>}> $metadata
     */
    public function withStreamToNativeTypeMetadata(array $metadata): self
    {
        return new self($this->name, $this->nativeToStreamTypeMetadata, $metadata);
    }

    public function withStreamToNativeType(Type $streamType, Type $nativeType, string|\Closure $transformer): self
    {
        $metadata = $this->streamToNativeTypeMetadata;

        foreach ($metadata as &$m) {
            if ($streamType != $m['stream']) {
                continue;
            }

            $m['native'] = $nativeType;
            $m['transformers'][] = $transformer;

            return new self($this->name, $this->nativeToStreamTypeMetadata, $metadata);
        }

        $metadata[] = ['native' => $nativeType, 'stream' => $streamType, 'transformers' => [$transformer]];

        return new self($this->name, $this->nativeToStreamTypeMetadata, $metadata);
    }
}
