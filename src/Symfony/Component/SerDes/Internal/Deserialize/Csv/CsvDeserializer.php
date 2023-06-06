<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize\Csv;

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Internal\Deserialize\Deserializer;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class CsvDeserializer extends Deserializer
{
    public function __construct(
        ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly CsvDecoder $decoder,
        private readonly bool $lazy,
    ) {
        parent::__construct($reflectionTypeExtractor);
    }

    public function deserialize(mixed $resource, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType || !$type->isList()) {
            throw new InvalidArgumentException(sprintf('Expecting type to be a list, but got "%s".', (string) $type));
        }

        $rows = $this->decoder->decode($resource, $context);

        $context['csv_headers'] = $rows->current();
        $context['csv_depth'] = 0;

        $rowsIterator = new \LimitIterator($rows, 1);
        $collectionValueType = $type->collectionValueType();

        if ($this->lazy) {
            return $this->lazyDeserialize($rowsIterator, $collectionValueType, $context);
        }

        return array_map(
            fn (array $r): mixed => $this->doDeserialize($r, $collectionValueType, $context),
            iterator_to_array($rowsIterator, preserve_keys: false),
        );
    }

    protected function deserializeScalar(mixed $data, Type $type, array $context): mixed
    {
        if (0 === $context['csv_depth']) {
            $data = reset($data);
        }

        if ('' === $data && $type->isNullable()) {
            return null;
        }

        return $data;
    }

    protected function deserializeList(mixed $data, Type $type, array $context): \Iterator
    {
        if (!\is_array($data)) {
            throw $this->tooDeepException();
        }

        $collectionValueType = $type->collectionValueType();
        ++$context['csv_depth'];

        foreach ($data as $value) {
            yield $this->doDeserialize($value, $collectionValueType, $context);
        }
    }

    protected function deserializeDict(mixed $data, Type $type, array $context): \Iterator
    {
        if (!\is_array($data)) {
            throw $this->tooDeepException();
        }

        $collectionValueType = $type->collectionValueType();
        ++$context['csv_depth'];

        foreach ($data as $index => $value) {
            yield $context['csv_headers'][$index] => $this->doDeserialize($value, $collectionValueType, $context);
        }
    }

    protected function deserializeObjectProperties(mixed $data, Type $type, array $context): \Iterator
    {
        if (!\is_array($data)) {
            throw $this->tooDeepException();
        }

        foreach ($data as $index => $value) {
            if ('' === $value) {
                continue;
            }

            yield $context['csv_headers'][$index] => $value;
        }
    }

    protected function deserializeMixed(mixed $data, array $context): mixed
    {
        if (0 === $context['csv_depth']) {
            return reset($data);
        }

        return $data;
    }

    protected function propertyValueCallable(Type|UnionType $type, mixed $data, mixed $value, array $context): callable
    {
        ++$context['csv_depth'];

        return fn () => $this->doDeserialize($value, $type, $context);
    }

    /**
     * @param \Iterator<list<mixed>> $rows
     * @param array<string, mixed>   $context
     *
     * @return \Iterator<mixed>
     */
    private function lazyDeserialize(\Iterator $rows, Type|UnionType $collectionValueType, array $context): \Iterator
    {
        foreach ($rows as $row) {
            yield $this->doDeserialize($row, $collectionValueType, $context);
        }
    }

    private function tooDeepException(): \Exception
    {
        return new InvalidArgumentException('Expecting type with at most two dimensions.');
    }
}
