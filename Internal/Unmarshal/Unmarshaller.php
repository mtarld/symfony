<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\TypeFactory;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class Unmarshaller
{
    /**
     * @var array<string, Type|UnionType>
     */
    private static array $typesCache = [];

    /**
     * @var array<string, Type|UnionType>
     */
    private static array $propertyTypesCache = [];

    /**
     * @var array<string, \ReflectionClass<object>>
     */
    private static array $classReflectionsCache = [];

    /**
     * @var array<string, ?callable>
     */
    private static array $objectHooksCache = [];

    /**
     * @var array<string, object>
     */
    private static array $instantiatedObjectsCache = [];

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
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][(string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = TypeFactory::createFromString($context['union_selector'][(string) $type]);
        }

        $result = null;

        if ('lazy' === $mode = $context['mode']) {
            if ($type->isScalar()) {
                if (null !== $scalar = $this->decoder->decode($resourceOrData, $context['boundary'][0], $context['boundary'][1], $context)) {
                    $result = match ($type->name()) {
                        'int' => (int) $scalar,
                        'float' => (float) $scalar,
                        'string' => (string) $scalar,
                        'bool' => (bool) $scalar,
                        default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
                    };
                }
            } elseif ($type->isCollection()) {
                $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;

                if (null !== $boundaries = $collectionSplitter->split($resourceOrData, $type, $context)) {
                    $result = $this->lazyUnmarshalCollectionItems($boundaries, $resourceOrData, $type->collectionValueType(), $context);
                    $result = $type->isIterable() ? $result : iterator_to_array($result);
                }
            } elseif ($type->isObject()) {
                if (null !== $boundaries = $this->dictSplitter->split($resourceOrData, $type, $context)) {
                    self::$objectHooksCache[$typeString = (string) $type] = self::$objectHooksCache[$typeString] ?? $this->hookExtractor->extractFromObject($type, $context);

                    if (null !== $hook = self::$objectHooksCache[$typeString]) {
                        /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
                        $hookResult = $hook($typeString, $context);

                        /** @var Type $type */
                        $type = isset($hookResult['type'])
                            ? self::$typesCache[$hookResult['type']] = self::$typesCache[$hookResult['type']] ?? TypeFactory::createFromString($hookResult['type'])
                            : $type;

                        $context = $hookResult['context'] ?? $context;
                    }

                    $reflection = self::$classReflectionsCache[$typeString] = self::$classReflectionsCache[$typeString] ?? new \ReflectionClass($type->className());

                    $result = $this->instantiateObject($reflection, $context);

                    foreach ($boundaries as $key => $boundary) {
                        try {
                            if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                                $hook(
                                    $reflection,
                                    $result,
                                    $key,
                                    function (string $type, array $context) use ($resourceOrData, $boundary): mixed {
                                        if (!isset(self::$typesCache[$type])) {
                                            self::$typesCache[$type] = TypeFactory::createFromString($type);
                                        }

                                        return $this->unmarshal($resourceOrData, self::$typesCache[$type], ['boundary' => $boundary] + $context);
                                    },
                                    $context,
                                );

                                continue;
                            }

                            if (!$reflection->hasProperty($key)) {
                                continue;
                            }

                            self::$propertyTypesCache[$key] = self::$propertyTypesCache[$key] ?? TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key)));

                            $result->{$key} = $this->unmarshal($resourceOrData, self::$propertyTypesCache[$key], ['boundary' => $boundary] + $context);
                        } catch (\TypeError $e) {
                            $exception = new UnexpectedTypeException($e->getMessage());

                            if (!($context['collect_errors'] ?? false)) {
                                throw $exception;
                            }

                            $context['collected_errors'][] = $exception;
                        }
                    }
                }
            } else {
                throw new UnsupportedTypeException($type);
            }
        } elseif ('eager' === $mode) {
            if ($type->isScalar()) {
                if (null !== $resourceOrData) {
                    $result = match ($type->name()) {
                        'int' => (int) $resourceOrData,
                        'float' => (float) $resourceOrData,
                        'string' => (string) $resourceOrData,
                        'bool' => (bool) $resourceOrData,
                        default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
                    };
                }
            } elseif ($type->isCollection()) {
                if (null !== $resourceOrData) {
                    $result = $this->unmarshalCollectionItems($resourceOrData, $type->collectionValueType(), $context);
                    $result = $type->isIterable() ? $result : iterator_to_array($result);
                }
            } elseif ($type->isObject()) {
                if (null !== $resourceOrData) {
                    self::$objectHooksCache[$typeString = (string) $type] = self::$objectHooksCache[$typeString] ?? $this->hookExtractor->extractFromObject($type, $context);

                    if (null !== $hook = self::$objectHooksCache[$typeString]) {
                        /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
                        $hookResult = $hook($typeString, $context);

                        /** @var Type $type */
                        $type = isset($hookResult['type'])
                        ? self::$typesCache[$hookResult['type']] = self::$typesCache[$hookResult['type']] ?? TypeFactory::createFromString($hookResult['type'])
                        : $type;

                        $context = $hookResult['context'] ?? $context;
                    }

                    $reflection = self::$classReflectionsCache[$typeString] = self::$classReflectionsCache[$typeString] ?? new \ReflectionClass($type->className());

                    // TODO override using context
                    $result = $this->instantiateObject($reflection, $context);

                    foreach ($resourceOrData as $key => $value) {
                        try {
                            if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
                                $hook(
                                    $reflection,
                                    $result,
                                    $key,
                                    function (string $type, array $context) use ($value): mixed {
                                        if (!isset(self::$typesCache[$type])) {
                                            self::$typesCache[$type] = TypeFactory::createFromString($type);
                                        }

                                        return $this->unmarshal($value, self::$typesCache[$type], $context);
                                    },
                                    $context,
                                );

                                continue;
                            }

                            if (!$reflection->hasProperty($key)) {
                                continue;
                            }

                            self::$propertyTypesCache[$key] = self::$propertyTypesCache[$key] ?? TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key)));

                            $result->{$key} = $this->unmarshal($value, self::$propertyTypesCache[$key], $context);
                        } catch (\TypeError $e) {
                            $exception = new UnexpectedTypeException($e->getMessage());

                            if (!($context['collect_errors'] ?? false)) {
                                throw $exception;
                            }

                            $context['collected_errors'][] = $exception;
                        }
                    }
                }
            } else {
                throw new UnsupportedTypeException($type);
            }
        } else {
            throw new InvalidArgumentException('TODO');
        }

        // if ('lazy' === $context['mode']) {
        //     $result = match (true) {
        //         $type->isScalar() => $this->lazyUnmarshalScalar($resourceOrData, $type, $context),
        //         $type->isCollection() => $this->lazyUnmarshalCollection($resourceOrData, $type, $context),
        //         $type->isObject() => $this->lazyUnmarshalObject($resourceOrData, $type, $context),
        //         default => throw new UnsupportedTypeException($type),
        //     };
        // } else {
        //     $result = match (true) {
        //         $type->isScalar() => $this->unmarshalScalar($resourceOrData, $type, $context),
        //         $type->isCollection() => $this->unmarshalCollection($resourceOrData, $type, $context),
        //         $type->isObject() => $this->unmarshalObject($resourceOrData, $type, $context),
        //         default => throw new UnsupportedTypeException($type),
        //     };
        // }

        if (null === $result && !$type->isNullable()) {
            throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
        }

        return $result;
    }

    // /**
    //  * @param array<string, mixed> $context
    //  */
    // private function unmarshalScalar(mixed $scalar, Type $type, array $context): int|string|bool|float|null
    // {
    //     if (null === $scalar) {
    //         return null;
    //     }
    //
    //     return match ($type->name()) {
    //         'int' => (int) $scalar,
    //         'float' => (float) $scalar,
    //         'string' => (string) $scalar,
    //         'bool' => (bool) $scalar,
    //         default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
    //     };
    // }
    //
    // /**
    //  * @param resource             $resource
    //  * @param array<string, mixed> $context
    //  */
    // private function lazyUnmarshalScalar(mixed $resource, Type $type, array $context): int|string|bool|float|null
    // {
    //     return $this->unmarshalScalar($this->decoder->decode($resource, $context['boundary'][0], $context['boundary'][1], $context), $type, $context);
    // }
    //
    // /**
    //  * @param list<mixed>|array<string, mixed>|null $collection
    //  * @param array<string, mixed>                  $context
    //  *
    //  * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
    //  */
    // private function unmarshalCollection(?array $collection, Type $type, array $context): \Iterator|array|null
    // {
    //     if (null === $collection) {
    //         return null;
    //     }
    //
    //     $result = $this->unmarshalCollectionItems($collection, $type->collectionValueType(), $context);
    //
    //     return $type->isIterable() ? $result : iterator_to_array($result);
    // }

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

    // /**
    //  * @param resource             $resource
    //  * @param array<string, mixed> $context
    //  *
    //  * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
    //  */
    // private function lazyUnmarshalCollection(mixed $resource, Type $type, array $context): \Iterator|array|null
    // {
    //     $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;
    //
    //     if (null === $boundaries = $collectionSplitter->split($resource, $type, $context)) {
    //         return null;
    //     }
    //
    //     $result = $this->lazyUnmarshalCollectionItems($boundaries, $resource, $type->collectionValueType(), $context);
    //
    //     return $type->isIterable() ? $result : iterator_to_array($result);
    // }

    /**
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param resource                         $resource
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function lazyUnmarshalCollectionItems(\Iterator $boundaries, mixed $resource, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->unmarshal($resource, $type, ['boundary' => $boundary] + $context);
        }
    }

    // /**
    //  * @param array<string, mixed>|null $values
    //  * @param array<string, mixed>      $context
    //  */
    // private function unmarshalObject(?array $values, Type $type, array $context): ?object
    // {
    //     if (null === $values) {
    //         return null;
    //     }
    //
    //     self::$objectHooksCache[$typeString = (string) $type] = self::$objectHooksCache[$typeString] ?? $this->hookExtractor->extractFromObject($type, $context);
    //
    //     if (null !== $hook = self::$objectHooksCache[$typeString]) {
    //         /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
    //         $hookResult = $hook($typeString, $context);
    //
    //         /** @var Type $type */
    //         $type = isset($hookResult['type'])
    //             ? self::$typesCache[$hookResult['type']] = self::$typesCache[$hookResult['type']] ?? TypeFactory::createFromString($hookResult['type'])
    //             : $type;
    //
    //         $context = $hookResult['context'] ?? $context;
    //     }
    //
    //     $reflection = self::$classReflectionsCache[$typeString] = self::$classReflectionsCache[$typeString] ?? new \ReflectionClass($type->className());
    //
    //     // TODO override using context
    //     $object = $this->instantiateObject($reflection, $context);
    //
    //     foreach ($values as $key => $value) {
    //         try {
    //             if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
    //                 $hook(
    //                     $reflection,
    //                     $object,
    //                     $key,
    //                     function (string $type, array $context) use ($value): mixed {
    //                         if (!isset(self::$typesCache[$type])) {
    //                             self::$typesCache[$type] = TypeFactory::createFromString($type);
    //                         }
    //
    //                         return $this->unmarshal($value, self::$typesCache[$type], $context);
    //                     },
    //                     $context,
    //                 );
    //
    //                 continue;
    //             }
    //
    //             if (!$reflection->hasProperty($key)) {
    //                 continue;
    //             }
    //
    //             self::$propertyTypesCache[$key] = self::$propertyTypesCache[$key] ?? TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key)));
    //
    //             $object->{$key} = $this->unmarshal($value, self::$propertyTypesCache[$key], $context);
    //         } catch (\TypeError $e) {
    //             $exception = new UnexpectedTypeException($e->getMessage());
    //
    //             if (!($context['collect_errors'] ?? false)) {
    //                 throw $exception;
    //             }
    //
    //             $context['collected_errors'][] = $exception;
    //         }
    //     }
    //
    //     return $object;
    // }
    //
    // /**
    //  * @param resource             $resource
    //  * @param array<string, mixed> $context
    //  */
    // private function lazyUnmarshalObject(mixed $resource, Type $type, array $context): ?object
    // {
    //     if (null === $boundaries = $this->dictSplitter->split($resource, $type, $context)) {
    //         return null;
    //     }
    //
    //     self::$objectHooksCache[$typeString = (string) $type] = self::$objectHooksCache[$typeString] ?? $this->hookExtractor->extractFromObject($type, $context);
    //
    //     if (null !== $hook = self::$objectHooksCache[$typeString]) {
    //         /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
    //         $hookResult = $hook($typeString, $context);
    //
    //         /** @var Type $type */
    //         $type = isset($hookResult['type'])
    //             ? self::$typesCache[$hookResult['type']] = self::$typesCache[$hookResult['type']] ?? TypeFactory::createFromString($hookResult['type'])
    //             : $type;
    //
    //         $context = $hookResult['context'] ?? $context;
    //     }
    //
    //     $reflection = self::$classReflectionsCache[$typeString] = self::$classReflectionsCache[$typeString] ?? new \ReflectionClass($type->className());
    //
    //     $object = $this->instantiateObject($reflection, $context);
    //
    //     foreach ($boundaries as $key => $boundary) {
    //         try {
    //             if (null !== $hook = $this->hookExtractor->extractFromKey($reflection->getName(), $key, $context)) {
    //                 $hook(
    //                     $reflection,
    //                     $object,
    //                     $key,
    //                     function (string $type, array $context) use ($resource, $boundary): mixed {
    //                         if (!isset(self::$typesCache[$type])) {
    //                             self::$typesCache[$type] = TypeFactory::createFromString($type);
    //                         }
    //
    //                         return $this->unmarshal($resource, self::$typesCache[$type], ['boundary' => $boundary] + $context);
    //                     },
    //                     $context,
    //                 );
    //
    //                 continue;
    //             }
    //
    //             if (!$reflection->hasProperty($key)) {
    //                 continue;
    //             }
    //
    //             self::$propertyTypesCache[$key] = self::$propertyTypesCache[$key] ?? TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($key)));
    //
    //             $object->{$key} = $this->unmarshal($resource, self::$propertyTypesCache[$key], ['boundary' => $boundary] + $context);
    //         } catch (\TypeError $e) {
    //             $exception = new UnexpectedTypeException($e->getMessage());
    //
    //             if (!($context['collect_errors'] ?? false)) {
    //                 throw $exception;
    //             }
    //
    //             $context['collected_errors'][] = $exception;
    //         }
    //     }
    //
    //     return $object;
    // }

    /**
     * @param \ReflectionClass<object> $class
     * @param array<string, mixed>     $context
     *
     * @throws InvalidConstructorArgumentException
     */
    private function instantiateObject(\ReflectionClass $class, array $context): object
    {
        if (isset(self::$instantiatedObjectsCache[$className = $class->getName()])) {
            return clone self::$instantiatedObjectsCache[$className];
        }

        if (null === $constructor = $class->getConstructor()) {
            return self::$instantiatedObjectsCache[$className] = new ($class->getName())();
        }

        if (!$constructor->isPublic()) {
            return self::$instantiatedObjectsCache[$className] = $class->newInstanceWithoutConstructor();
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

        return self::$instantiatedObjectsCache[$className] = ($validContructor ? $class->newInstanceArgs($parameters) : $class->newInstanceWithoutConstructor());
    }
}
