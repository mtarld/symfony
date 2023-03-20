<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Instantiator\InstantiatorInterface;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Internal\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Unmarshaller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $cache = [
        'type' => [],
        'property_type' => [],
        'class_reflection' => [],
        'class_has_property' => [],
    ];

    public function __construct(
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly DecoderInterface $decoder,
        private readonly ListSplitterInterface $listSplitter,
        private readonly DictSplitterInterface $dictSplitter,
        private readonly InstantiatorInterface $instantiator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function unmarshal(mixed $resourceOrData, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][$typeString = (string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            if (!isset(self::$cache['type'][$typeString])) {
                self::$cache['type'][$typeString] = TypeFactory::createFromString($context['union_selector'][$typeString]);
            }

            /** @var Type $type */
            $type = self::$cache['type'][$typeString];
        }

        $result = match (true) {
            $type->isScalar() => $this->unmarshalScalar($context['lazy_reading'], $resourceOrData, $type, $context),
            $type->isCollection() => $this->unmarshalCollection($context['lazy_reading'], $resourceOrData, $type, $context),
            $type->isObject() => $this->unmarshalObject($context['lazy_reading'], $resourceOrData, $type, $context),

            default => throw new UnsupportedTypeException($type),
        };

        if (null === $result && !$type->isNullable()) {
            throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function unmarshalScalar(bool $lazy, mixed $resourceOrData, Type $type, array $context): int|string|bool|float|null
    {
        $data = $lazy ? $this->decoder->decode($resourceOrData, $context['boundary'][0], $context['boundary'][1], $context) : $resourceOrData;

        if (null === $data) {
            return null;
        }

        return match ($type->name()) {
            'int' => (int) $data,
            'float' => (float) $data,
            'string' => (string) $data,
            'bool' => (bool) $data,
            default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
     */
    private function unmarshalCollection(bool $lazy, mixed $resourceOrData, Type $type, array $context): \Iterator|array|null
    {
        if ($lazy) {
            $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;

            if (null === $boundaries = $collectionSplitter->split($resourceOrData, $type, $context)) {
                return null;
            }

            $data = $this->lazyUnmarshalCollectionItems($boundaries, $resourceOrData, $type->collectionValueType(), $context);
        } else {
            if (null === $resourceOrData) {
                return null;
            }

            $data = $this->unmarshalCollectionItems($resourceOrData, $type->collectionValueType(), $context);
        }

        return $type->isIterable() ? $data : iterator_to_array($data);
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
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param resource                         $resource
     * @param array<string, mixed>             $context
     * @param \Iterator<mixed,mixed>           $boundaries
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
     * @param array<string, mixed> $context
     */
    private function unmarshalObject(bool $lazy, mixed $resourceOrData, Type $type, array $context): ?object
    {
        /** @var array<string, mixed>|null $data */
        $data = $lazy ? $this->dictSplitter->split($resourceOrData, $type, $context) : $resourceOrData;

        if (null === $data) {
            return null;
        }

        $hook = null;

        if (isset($context['hooks']['unmarshal'][$className = $type->className()])) {
            $hook = $context['hooks']['unmarshal'][$className];
        } elseif (isset($context['hooks']['unmarshal']['object'])) {
            $hook = $context['hooks']['unmarshal']['object'];
        }

        if (null !== $hook) {
            /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
            $hookResult = $hook((string) $type, $context);

            if (isset($hookResult['type'])) {
                if (!isset(self::$cache['type'][$hookResult['type']])) {
                    self::$cache['type'][$hookResult['type']] = TypeFactory::createFromString($hookResult['type']);
                }

                /** @var Type $type */
                $type = self::$cache['type'][$hookResult['type']];
            }

            $context = $hookResult['context'] ?? $context;
        }

        if (!isset(self::$cache['class_reflection'][$typeString = (string) $type])) {
            self::$cache['class_reflection'][$typeString] = new \ReflectionClass($type->className());
        }

        /** @var \ReflectionClass<object> $reflection */
        $reflection = self::$cache['class_reflection'][$typeString];

        /** @var array<string, callable(): mixed> $propertiesValues */
        $propertiesValues = [];

        foreach ($data as $k => $v) {
            $hook = null;

            if (isset($context['hooks']['unmarshal'][($className = $reflection->getName()).'['.$k.']'])) {
                $hook = $context['hooks']['unmarshal'][$className.'['.$k.']'];
            } elseif (isset($context['hooks']['unmarshal']['property'])) {
                $hook = $context['hooks']['unmarshal']['property'];
            }

            $propertyName = $k;

            if (null !== $hook) {
                /** @var array{name?: string, value_provider?: callable(): mixed, context?: array<string, mixed>} $hookResult */
                $hookResult = $hook(
                    $reflection,
                    $k,
                    function (string $type, array $context) use ($v, $resourceOrData, $lazy): mixed {
                        if (!isset(self::$cache['type'][$type])) {
                            self::$cache['type'][$type] = TypeFactory::createFromString($type);
                        }

                        if ($lazy) {
                            return $this->unmarshal($resourceOrData, self::$cache['type'][$type], ['boundary' => $v] + $context);
                        }

                        return $this->unmarshal($v, self::$cache['type'][$type], $context);
                    },
                    $context,
                );

                $propertyName = $hookResult['name'] ?? $propertyName;
                $context = $hookResult['context'] ?? $context;
            }

            if (!isset(self::$cache['class_has_property'][$propertyIdentifier = $typeString.$propertyName])) {
                self::$cache['class_has_property'][$propertyIdentifier] = $reflection->hasProperty($propertyName);
            }

            if (!self::$cache['class_has_property'][$propertyIdentifier]) {
                continue;
            }

            if (isset($hookResult['value_provider'])) {
                $propertiesValues[$propertyName] = $hookResult['value_provider'];

                continue;
            }

            if (!isset(self::$cache['property_type'][$propertyIdentifier])) {
                self::$cache['property_type'][$propertyIdentifier] = TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($propertyName)));
            }

            $propertiesValues[$propertyName] = $lazy
                ? fn () => $this->unmarshal($resourceOrData, self::$cache['property_type'][$propertyIdentifier], ['boundary' => $v] + $context)
                : fn () => $this->unmarshal($v, self::$cache['property_type'][$propertyIdentifier], $context);
        }

        if (isset($context['instantiator'])) {
            return $context['instantiator']($reflection, $propertiesValues, $context);
        }

        return ($this->instantiator)($reflection, $propertiesValues, $context);
    }
}
