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

use Symfony\Component\SerDes\Exception\LogicException;
use Symfony\Component\SerDes\Exception\UnexpectedValueException;
use Symfony\Component\SerDes\Exception\UnsupportedTypeException;
use Symfony\Component\SerDes\Instantiator\InstantiatorInterface;
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Deserializer
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
    public function deserialize(mixed $resourceOrData, Type|UnionType $type, array $context): mixed
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
            $type->isScalar() => $this->deserializeScalar($context['lazy_reading'], $resourceOrData, $type, $context),
            $type->isCollection() => $this->deserializeCollection($context['lazy_reading'], $resourceOrData, $type, $context),
            $type->isEnum() => $this->deserializeEnum($context['lazy_reading'], $resourceOrData, $type, $context),
            $type->isObject() => $this->deserializeObject($context['lazy_reading'], $resourceOrData, $type, $context),

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
    private function deserializeScalar(bool $lazy, mixed $resourceOrData, Type $type, array $context): int|string|bool|float|null
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
    private function deserializeCollection(bool $lazy, mixed $resourceOrData, Type $type, array $context): \Iterator|array|null
    {
        if ($lazy) {
            $collectionSplitter = $type->isDict() ? $this->dictSplitter : $this->listSplitter;

            if (null === $boundaries = $collectionSplitter->split($resourceOrData, $type, $context)) {
                return null;
            }

            $data = $this->lazyDeserializeCollectionItems($boundaries, $resourceOrData, $type->collectionValueType(), $context);
        } else {
            if (null === $resourceOrData) {
                return null;
            }

            $data = $this->deserializeCollectionItems($resourceOrData, $type->collectionValueType(), $context);
        }

        return $type->isIterable() ? $data : iterator_to_array($data);
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

    /**
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param resource                         $resource
     * @param array<string, mixed>             $context
     * @param \Iterator<mixed,mixed>           $boundaries
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function lazyDeserializeCollectionItems(\Iterator $boundaries, mixed $resource, Type|UnionType $type, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->deserialize($resource, $type, ['boundary' => $boundary] + $context);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deserializeEnum(bool $lazy, mixed $resourceOrData, Type $type, array $context): ?\BackedEnum
    {
        $data = $lazy ? $this->decoder->decode($resourceOrData, $context['boundary'][0], $context['boundary'][1], $context) : $resourceOrData;

        if (null === $data) {
            return null;
        }

        try {
            return ($type->className())::from($data);
        } catch (\ValueError $e) {
            throw new UnexpectedValueException(sprintf('Unexpected "%s" value for "%s" backed enumeration.', $data, $type));
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deserializeObject(bool $lazy, mixed $resourceOrData, Type $type, array $context): ?object
    {
        /** @var array<string, mixed>|null $data */
        $data = $lazy ? $this->dictSplitter->split($resourceOrData, $type, $context) : $resourceOrData;

        if (null === $data) {
            return null;
        }

        $hook = null;

        if (isset($context['hooks']['deserialize'][$className = $type->className()])) {
            $hook = $context['hooks']['deserialize'][$className];
        } elseif (isset($context['hooks']['deserialize']['object'])) {
            $hook = $context['hooks']['deserialize']['object'];
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

            if (isset($context['hooks']['deserialize'][($className = $reflection->getName()).'['.$k.']'])) {
                $hook = $context['hooks']['deserialize'][$className.'['.$k.']'];
            } elseif (isset($context['hooks']['deserialize']['property'])) {
                $hook = $context['hooks']['deserialize']['property'];
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
                            return $this->deserialize($resourceOrData, self::$cache['type'][$type], ['boundary' => $v] + $context);
                        }

                        return $this->deserialize($v, self::$cache['type'][$type], $context);
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
                ? fn () => $this->deserialize($resourceOrData, self::$cache['property_type'][$propertyIdentifier], ['boundary' => $v] + $context)
                : fn () => $this->deserialize($v, self::$cache['property_type'][$propertyIdentifier], $context);
        }

        if (isset($context['instantiator'])) {
            return $context['instantiator']($reflection, $propertiesValues, $context);
        }

        return ($this->instantiator)($reflection, $propertiesValues, $context);
    }
}
