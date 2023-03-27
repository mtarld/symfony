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
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class Deserializer
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $cache = [
        'type' => [],
        'property_type' => [],
        'class_reflection' => [],
        'class_has_property' => [],
    ];

    public function __construct(
        protected readonly ReflectionTypeExtractor $reflectionTypeExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function deserialize(mixed $data, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][$typeString = (string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = (self::$cache['type'][$typeString] ??= TypeFactory::createFromString($context['union_selector'][$typeString]));
        }

        $result = match (true) {
            $type->isScalar() => $this->deserializeScalar($data, $type, $context),
            $type->isCollection() => $this->deserializeCollection($data, $type, $context),
            $type->isEnum() => $this->deserializeEnum($data, $type, $context),
            $type->isObject() => $this->deserializeObject($data, $type, $context),

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
    protected function deserializeScalar(mixed $data, Type $type, array $context): int|string|bool|float|null
    {
        if (null === $data) {
            return null;
        }

        try {
            return match ($type->name()) {
                'int' => (int) $data,
                'float' => (float) $data,
                'string' => (string) $data,
                'bool' => (bool) $data,
                default => throw new LogicException(sprintf('Unhandled "%s" scalar cast', $type->name())),
            };
        } catch (\Throwable) {
            throw new UnexpectedValueException(sprintf('Cannot cast "%s" to "%s"', get_debug_type($data), (string) $type));
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>|list<mixed>|array<string, mixed>|null
     */
    protected function deserializeCollection(mixed $data, Type $type, array $context): \Iterator|array|null
    {
        if (null === $data) {
            return null;
        }

        $data = $this->deserializeCollectionItems($data, $type->collectionValueType(), $context);

        return $type->isIterable() ? $data : iterator_to_array($data);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function deserializeEnum(mixed $data, Type $type, array $context): ?\BackedEnum
    {
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
    protected function deserializeObject(mixed $data, Type $type, array $context): ?object
    {
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
                /** @var Type $type */
                $type = (self::$cache['type'][$hookResult['type']] ??= TypeFactory::createFromString($hookResult['type']));
            }

            $context = $hookResult['context'] ?? $context;
        }

        /** @var \ReflectionClass<object> $reflection */
        $reflection = (self::$cache['class_reflection'][$typeString = (string) $type] ??= new \ReflectionClass($type->className()));

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
                $hookResult = $this->executePropertyHook($hook, $reflection, $k, $v, $data, $context);

                $propertyName = $hookResult['name'] ?? $propertyName;
                $context = $hookResult['context'] ?? $context;
            }

            self::$cache['class_has_property'][$propertyIdentifier = $typeString.$propertyName] ??= $reflection->hasProperty($propertyName);

            if (!self::$cache['class_has_property'][$propertyIdentifier]) {
                continue;
            }

            if (isset($hookResult['value_provider'])) {
                $propertiesValues[$propertyName] = $hookResult['value_provider'];

                continue;
            }

            self::$cache['property_type'][$propertyIdentifier] ??= TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($propertyName)));

            $propertiesValues[$propertyName] = $this->propertyValue(self::$cache['property_type'][$propertyIdentifier], $v, $data, $context);
        }

        if (isset($context['instantiator'])) {
            return $context['instantiator']($reflection, $propertiesValues, $context);
        }

        $object = new ($reflection->getName())();

        foreach ($propertiesValues as $property => $value) {
            try {
                $object->{$property} = $value();
            } catch (\TypeError|UnexpectedValueException $e) {
                $exception = new UnexpectedValueException($e->getMessage(), previous: $e);

                if (!($context['collect_errors'] ?? false)) {
                    throw $exception;
                }

                $context['collected_errors'][] = $exception;
            }
        }

        return $object;
    }

    /**
     * @param callable(\ReflectionClass<object>, string, callable(string, array<string, mixed>): mixed, array<string, mixed>): array{name?: string, value_provider?: callable(): mixed, context?: array<string, mixed>} $hook
     * @param \ReflectionClass<object>                                                                                                                                                                                  $reflection
     * @param array<string, mixed>                                                                                                                                                                                      $context
     *
     * @return array{name?: string, value_provider?: callable(): mixed, context?: array<string, mixed>}
     */
    protected function executePropertyHook(callable $hook, \ReflectionClass $reflection, string $key, mixed $value, mixed $data, array $context): array
    {
        return $hook(
            $reflection,
            $key,
            function (string $type, array $context) use ($value): mixed {
                return $this->deserialize($value, self::$cache['type'][$type] ??= TypeFactory::createFromString($type), $context);
            },
            $context,
        );
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return callable(): mixed
     */
    protected function propertyValue(Type|UnionType $type, mixed $value, mixed $data, array $context): callable
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
