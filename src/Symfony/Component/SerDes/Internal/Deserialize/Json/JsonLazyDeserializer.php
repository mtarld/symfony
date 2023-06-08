<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize\Json;

use Symfony\Component\SerDes\Internal\Deserialize\Deserializer;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonLazyDeserializer extends Deserializer
{
    public function __construct(
        ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly JsonDecoder $decoder,
        private readonly JsonListSplitter $listSplitter,
        private readonly JsonDictSplitter $dictSplitter,
    ) {
        parent::__construct($reflectionTypeExtractor);
    }

    public function deserialize(mixed $resource, Type $type, array $context): mixed
    {
        return $this->doDeserialize($resource, $type, $context);
    }

    protected function deserializeScalar(mixed $resource, Type $type, array $context): mixed
    {
        return $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);
    }

    protected function deserializeList(mixed $resource, Type $type, array $context): ?\Iterator
    {
        if (null === $boundaries = $this->listSplitter->split($resource, $type, $context)) {
            return null;
        }

        return $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $context);
    }

    protected function deserializeDict(mixed $resource, Type $type, array $context): ?\Iterator
    {
        if (null === $boundaries = $this->dictSplitter->split($resource, $type, $context)) {
            return null;
        }

        return $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $context);
    }

    protected function deserializeObjectProperties(mixed $resource, Type $type, array $context): ?array
    {
        if (null === $boundaries = $this->dictSplitter->split($resource, $type, $context)) {
            return null;
        }

        return iterator_to_array($boundaries);
    }

    protected function deserializeMixed(mixed $resource, array $context): mixed
    {
        return $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);
    }

    protected function deserializeObjectPropertyValue(Type $type, mixed $resource, mixed $value, array $context): mixed
    {
        return $this->doDeserialize($resource, $type, ['boundary' => $value] + $context);
    }

    /**
     * @param resource                         $resource
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function deserializeCollectionItems(mixed $resource, \Iterator $boundaries, Type $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->doDeserialize($resource, $type, ['boundary' => $boundary] + $context);
        }
    }
}
