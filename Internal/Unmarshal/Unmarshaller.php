<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class Unmarshaller
{
    public function __construct(
        private readonly HookExtractor $hookExtractor,
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly DecoderInterface $decoder,
        private readonly ListSplitterInterface $listSplitter,
        private readonly DictSplitterInterface $dictSplitter,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function unmarshal(mixed $resourceOrData, Type|UnionType $type, array $context): mixed
    {
        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            $hookResult = $hook((string) $type, $context);

            $type = isset($hookResult['type']) ? Type::createFromString($hookResult['type']) : $type;
            $context = $hookResult['context'] ?? $context;
        }

        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][(string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = Type::createFromString($context['union_selector'][(string) $type]);
        }

        if ('lazy' === $context['mode']) {
            $result = match (true) {
                $type->isScalar() => $this->lazyUnmarshalScalar($resourceOrData, $type, $context),
                $type->isCollection() => $this->lazyUnmarshalCollection($resourceOrData, $type, $context),
                $type->isObject() => $this->lazyUnmarshalObject($resourceOrData, $type, $context),
                default => throw new UnsupportedTypeException($type),
            };
        } else {
            $result = match (true) {
                $type->isScalar() => $this->unmarshalScalar($resourceOrData, $type, $context),
                $type->isCollection() => $this->unmarshalCollection($resourceOrData, $type, $context),
                $type->isObject() => $this->unmarshalObject($resourceOrData, $type, $context),
                default => throw new UnsupportedTypeException($type),
            };
        }

        if (null === $result && !$type->isNullable()) {
            throw new UnexpectedValueException('TODO');
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function unmarshalScalar(mixed $scalar, Type $type, array $context): int|string|bool|float|null
    {
        if (null === $scalar) {
            return null;
        }

        return match ($type->name()) {
            'int' => (int) $scalar,
            'float' => (float) $scalar,
            'string' => (string) $scalar,
            'bool' => (bool) $scalar,
            default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
        };
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    private function lazyUnmarshalScalar(mixed $resource, Type $type, array $context): int|string|bool|float|null
    {
        return $this->unmarshalScalar($this->decoder->decode($resource, $context['boundary'], $context), $type, $context);
    }

    /**
     * @param list<mixed>|array<string, mixed>|null $collection
     * @param array<string, mixed>                  $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
     */
    private function unmarshalCollection(?array $collection, Type $type, array $context): \Iterator|array|null
    {
        if (null === $collection) {
            return null;
        }

        $result = $this->unmarshalCollectionItems($collection, $type->collectionValueType(), $context);

        return $type->isIterable() ? $result : iterator_to_array($result);
    }

    /**
     * @param array<string, mixed>|list<mixed> $collection
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function unmarshalCollectionItems(array $collection, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($collection as $key => $value) {
            yield $key => $this->unmarshal($value, $type, $context);
        }
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
     */
    private function lazyUnmarshalCollection(mixed $resource, Type $type, array $context): \Iterator|array|null
    {
        $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;

        if (null === $boundaries = $collectionSplitter->split($resource, $type, $context)) {
            return null;
        }

        $result = $this->lazyUnmarshalCollectionItems($boundaries, $resource, $type->collectionValueType(), $context);

        return $type->isIterable() ? $result : iterator_to_array($result);
    }

    /**
     * @param \Iterator<Boundary>  $boundaries
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function lazyUnmarshalCollectionItems(\Iterator $boundaries, mixed $resource, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->unmarshal($resource, $type, ['boundary' => $boundary] + $context);
        }
    }

    /**
     * @param array<string, mixed>|null $values
     * @param array<string, mixed>      $context
     */
    private function unmarshalObject(?array $values, Type $type, array $context): ?object
    {
        if (null === $values) {
            return null;
        }

        $reflection = new \ReflectionClass($type->className());
        // TODO override using context
        $object = $this->instantiateObject($reflection, $context);

        foreach ($values as $key => $value) {
            try {
                if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                    $hook(
                        $reflection,
                        $object,
                        $key,
                        fn (string $type, array $context): mixed => $this->unmarshal($value, Type::createFromString($type), $context),
                        $context,
                    );

                    continue;
                }

                // TODO test
                if (!$reflection->hasProperty($key)) {
                    continue;
                }

                $object->{$key} = $this->unmarshal(
                    $value,
                    Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key))),
                    $context,
                );
            } catch (\TypeError $e) {
                $exception = new UnexpectedTypeException($e->getMessage());
                if (!($context['collect_errors'] ?? false)) {
                    throw $exception;
                }

                $context['collected_errors'][] = $exception;
            }
        }

        return $object;
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    private function lazyUnmarshalObject(mixed $resource, Type $type, array $context): ?object
    {
        if (null === $boundaries = $this->dictSplitter->split($resource, $type, $context)) {
            return null;
        }

        $reflection = new \ReflectionClass($type->className());
        $object = $this->instantiateObject($reflection, $context);

        foreach ($boundaries as $key => $boundary) {
            try {
                if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                    $hook(
                        $reflection,
                        $object,
                        $key,
                        fn (string $type, array $context): mixed => $this->unmarshal($resource, Type::createFromString($type), ['boundary' => $boundary] + $context),
                        $context,
                    );

                    continue;
                }

                if (!$reflection->hasProperty($key)) {
                    continue;
                }

                $object->{$key} = $this->unmarshal(
                    $resource,
                    Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key))),
                    ['boundary' => $boundary] + $context,
                );
            } catch (\TypeError $e) {
                $exception = new UnexpectedTypeException($e->getMessage());
                if (!($context['collect_errors'] ?? false)) {
                    throw $exception;
                }

                $context['collected_errors'][] = $exception;
            }
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<T>  $class
     * @param array<string, mixed> $context
     *
     * @return T
     *
     * @throws InvalidConstructorArgumentException
     */
    private function instantiateObject(\ReflectionClass $class, array $context): object
    {
        if (null === $constructor = $class->getConstructor()) {
            return new ($class->getName())();
        }

        if (!$constructor->isPublic()) {
            return $class->newInstanceWithoutConstructor();
        }

        $parameters = [];
        $validContructor = true;

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            if ($parameter->getType()?->allowsNull()) {
                $parameters[] = null;

                continue;
            }

            $exception = new InvalidConstructorArgumentException($parameter->getName(), $class->getName());
            if (!($context['collect_errors'] ?? false)) {
                throw $exception;
            }

            $context['collected_errors'][] = $exception;
            $validContructor = false;
        }

        return $validContructor ? $class->newInstanceArgs($parameters) : $class->newInstanceWithoutConstructor();
    }
}
