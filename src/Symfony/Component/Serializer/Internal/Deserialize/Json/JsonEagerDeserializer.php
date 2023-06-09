<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Deserialize\Json;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Internal\Deserialize\Deserializer;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonEagerDeserializer extends Deserializer
{
    public function __construct(
        ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly JsonDecoder $decoder,
    ) {
        parent::__construct($reflectionTypeExtractor);
    }

    public function deserialize(mixed $resource, Type $type, array $context): mixed
    {
        $data = $this->decoder->decode($resource, 0, -1, $context);

        return $this->doDeserialize($data, $type, $context);
    }

    protected function deserializeScalar(mixed $data, Type $type, array $context): mixed
    {
        return $data;
    }

    protected function deserializeList(mixed $data, Type $type, array $context): ?\Iterator
    {
        if (null === $data) {
            return null;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException(sprintf('Unexpected value for a list, expected "array", got "%s".', get_debug_type($data)));
        }

        return $this->deserializeCollectionItems($data, $type->collectionValueType(), $context);
    }

    protected function deserializeDict(mixed $data, Type $type, array $context): ?\Iterator
    {
        if (null === $data) {
            return null;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException(sprintf('Unexpected value for a dict, expected "array", got "%s".', get_debug_type($data)));
        }

        return $this->deserializeCollectionItems($data, $type->collectionValueType(), $context);
    }

    protected function deserializeObjectProperties(mixed $data, Type $type, array $context): ?array
    {
        if (null === $data) {
            return null;
        }

        if (!\is_array($data)) {
            throw new UnexpectedValueException(sprintf('Unexpected value for object properties, expected "array", got "%s".', get_debug_type($data)));
        }

        return $data;
    }

    protected function deserializeMixed(mixed $data, array $context): mixed
    {
        return $data;
    }

    protected function deserializeObjectPropertyValue(Type $type, mixed $resource, mixed $value, array $context): mixed
    {
        return $this->doDeserialize($value, $type, $context);
    }

    /**
     * @param array<string, mixed>|list<mixed> $collection
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function deserializeCollectionItems(array $collection, Type $type, array $context): \Iterator
    {
        foreach ($collection as $key => $value) {
            yield $key => $this->doDeserialize($value, $type, $context);
        }
    }
}
