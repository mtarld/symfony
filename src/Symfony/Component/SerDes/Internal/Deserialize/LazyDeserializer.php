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
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
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

    /**
     * @param resource $resource
     */
    protected function deserializeScalar(mixed $resource, Type $type, array $context): int|string|bool|float|null
    {
        $data = $this->decoder->decode($resource, $context['boundary'][0], $context['boundary'][1], $context);

        return parent::deserializeScalar($data, $type, $context);
    }

    /**
     * @param resource $resource
     */
    protected function deserializeCollection(mixed $resource, Type $type, array $context): \Iterator|array|null
    {
        $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;

        if (null === $boundaries = $collectionSplitter->split($resource, $type, $context)) {
            return null;
        }

        $data = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $context);

        return $type->isIterable() ? $data : iterator_to_array($data);
    }

    /**
     * @param resource $resource
     */
    protected function deserializeEnum(mixed $resource, Type $type, array $context): ?\BackedEnum
    {
        $data = $this->decoder->decode($resource, $context['boundary'][0], $context['boundary'][1], $context);

        return parent::deserializeEnum($data, $type, $context);
    }

    /**
     * @param resource $resource
     */
    protected function deserializeObject(mixed $resource, Type $type, array $context): ?object
    {
        $data = $this->dictSplitter->split($resource, $type, $context);

        return parent::deserializeObject($data, $type, $context);
    }

    /**
     * @param resource $resource
     */
    protected function executePropertyHook(callable $hook, \ReflectionClass $reflection, string $key, mixed $boundary, mixed $resource, array $context): array
    {
        return $hook(
            $reflection,
            $key,
            function (string $type, array $context) use ($resource, $boundary): mixed {
                return $this->deserialize($resource, self::$cache['type'][$type] ??= TypeFactory::createFromString($type), ['boundary' => $boundary] + $context);
            },
            $context,
        );
    }

    /**
     * @param resource $resource
     */
    protected function propertyValue(Type|UnionType $type, mixed $boundary, mixed $resource, array $context): callable
    {
        return fn () => $this->deserialize($resource, $type, ['boundary' => $boundary] + $context);
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
