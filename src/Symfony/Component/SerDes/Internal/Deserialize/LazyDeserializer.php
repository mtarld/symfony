<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize;

use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @extends Deserializer<resource>
 */
final class LazyDeserializer extends Deserializer
{
    public function __construct(
        ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly DecoderInterface $decoder,
        private readonly ListSplitterInterface $listSplitter,
        private readonly DictSplitterInterface $dictSplitter,
    ) {
        parent::__construct($reflectionTypeExtractor);
    }

    protected function deserializeScalar(mixed $data, Type $type, array $context): mixed
    {
        return $this->decoder->decode($data, $context['boundary'][0], $context['boundary'][1], $context);
    }

    protected function deserializeEnum(mixed $data, Type $type, array $context): mixed
    {
        return $this->decoder->decode($data, $context['boundary'][0], $context['boundary'][1], $context);
    }

    protected function deserializeList(mixed $data, Type $type, array $context): ?\Iterator
    {
        if (null === $boundaries = $this->listSplitter->split($data, $type, $context)) {
            return null;
        }

        return $this->deserializeCollectionItems($data, $boundaries, $type->collectionValueType(), $context);
    }

    protected function deserializeDict(mixed $data, Type $type, array $context): ?\Iterator
    {
        if (null === $boundaries = $this->dictSplitter->split($data, $type, $context)) {
            return null;
        }

        return $this->deserializeCollectionItems($data, $boundaries, $type->collectionValueType(), $context);
    }

    protected function deserializeObjectProperties(mixed $data, Type $type, array $context): ?\Iterator
    {
        return $this->dictSplitter->split($data, $type, $context);
    }

    protected function propertyValueCallable(Type|UnionType $type, mixed $data, mixed $value, array $context): callable
    {
        return fn () => $this->deserialize($data, $type, ['boundary' => $value] + $context);
    }

    /**
     * @param resource                         $resource
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function deserializeCollectionItems(mixed $resource, \Iterator $boundaries, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->deserialize($resource, $type, ['boundary' => $boundary] + $context);
        }
    }
}
