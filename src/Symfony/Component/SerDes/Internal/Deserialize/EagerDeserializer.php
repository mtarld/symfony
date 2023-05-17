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

use Symfony\Component\SerDes\Exception\UnexpectedValueException;
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @extends Deserializer<mixed>
 */
final class EagerDeserializer extends Deserializer
{
    protected function deserializeScalar(mixed $data, Type $type, array $context): mixed
    {
        return $data;
    }

    protected function deserializeEnum(mixed $data, Type $type, array $context): mixed
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

    /**
     * @return array<string, mixed>|null
     */
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

    protected function propertyValueCallable(Type|UnionType $type, mixed $data, mixed $value, array $context): callable
    {
        return fn () => $this->deserialize($value, $type, $context);
    }

    /**
     * @param array<string, mixed>|list<mixed> $collection
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function deserializeCollectionItems(array $collection, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($collection as $key => $value) {
            yield $key => $this->deserialize($value, $type, $context);
        }
    }
}
