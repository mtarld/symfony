<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Deserialize;

use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class Deserializer
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
    abstract protected function deserializeScalar(mixed $dataOrResource, Type $type, array $context): mixed;

    /**
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|null
     */
    abstract protected function deserializeList(mixed $dataOrResource, Type $type, array $context): ?\Iterator;

    /**
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string, mixed>|null
     */
    abstract protected function deserializeDict(mixed $dataOrResource, Type $type, array $context): ?\Iterator;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>|null
     */
    abstract protected function deserializeObjectProperties(mixed $dataOrResource, Type $type, array $context): ?array;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function deserializeMixed(mixed $dataOrResource, array $context): mixed;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function deserializeObjectPropertyValue(Type $type, mixed $dataOrResource, mixed $value, array $context): mixed;

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    abstract public function deserialize(mixed $resource, Type $type, array $context): mixed;

    /**
     * @param array<string, mixed> $context
     */
    protected function doDeserialize(mixed $dataOrResource, Type $type, array $context): mixed
    {
        if ($type->isUnion()) {
            $selectedType = ($context['union_selector'][$typeString = (string) $type] ?? null);
            if (null === $selectedType) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = \is_string($selectedType)
                ? (self::$cache['type'][$selectedType] ??= TypeFactory::createFromString($selectedType))
                : $selectedType;
        }

        if ($type->isScalar()) {
            $scalar = $this->deserializeScalar($dataOrResource, $type, $context);

            if (null === $scalar) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            try {
                return match ($type->name()) {
                    'int' => (int) $scalar,
                    'float' => (float) $scalar,
                    'string' => (string) $scalar,
                    'bool' => (bool) $scalar,
                    default => throw new LogicException(sprintf('Unhandled "%s" scalar cast', $type->name())),
                };
            } catch (\Throwable) {
                throw new UnexpectedValueException(sprintf('Cannot cast "%s" to "%s"', get_debug_type($scalar), (string) $type));
            }
        }

        if ($type->isEnum()) {
            $enum = $this->deserializeScalar($dataOrResource, $type, $context);

            if (null === $enum) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            try {
                return ($type->className())::from($enum);
            } catch (\ValueError $e) {
                throw new UnexpectedValueException(sprintf('Unexpected "%s" value for "%s" backed enumeration.', $enum, $type));
            }
        }

        if ($type->isCollection()) {
            $collection = match (true) {
                $type->isList() => $this->deserializeList($dataOrResource, $type, $context),
                $type->isDict() => $this->deserializeDict($dataOrResource, $type, $context),
                default => $this->deserializeMixed($dataOrResource, $context),
            };

            if (null === $collection) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            return $type->isIterable() ? $collection : iterator_to_array($collection);
        }

        if ($type->isObject()) {
            if (!$type->hasClass()) {
                $object = new \stdClass();
                foreach ($this->deserializeMixed($dataOrResource, $context) as $property => $value) {
                    $object->{$property} = $value;
                }

                return $object;
            }

            $className = $type->className();

            /** @var \ReflectionClass<object> $reflection */
            $reflection = (self::$cache['class_reflection'][$typeString = (string) $type] ??= new \ReflectionClass($className));
            $values = $this->deserializeObjectProperties($dataOrResource, $type, $context);

            if (null === $values) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $properties = [];
            foreach ($values as $name => $value) {
                $properties[$name] = [
                    'name' => $name,
                    'value_provider' => fn (Type $type) => $this->deserializeObjectPropertyValue($type, $dataOrResource, $value, $context),
                ];
            }

            if (null !== $hook = $context['hooks']['deserialize'][$className] ?? $context['hooks']['deserialize']['object'] ?? null) {
                /** @var array{properties?: array<string, array{name: string, value_provider: callable(Type): mixed}>, context?: array<string, mixed>} $hookResult */
                $hookResult = $hook($type, $properties, $context);

                $context = $hookResult['context'] ?? $context;
                $properties = $hookResult['properties'] ?? $properties;
            }

            if (isset($context['instantiator'])) {
                return $context['instantiator']($reflection, array_map(function (array $property) use ($typeString, $reflection): callable {
                    $name = $property['name'];
                    $type = (self::$cache['property_type'][$typeString.$name] ??= $this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($name)));

                    return fn () => $property['value_provider']($type);
                }, $properties), $context);
            }

            $object = new $className();

            foreach ($properties as $property) {
                $name = $property['name'];

                self::$cache['class_has_property'][$identifier = $typeString.$name] ??= $reflection->hasProperty($name);
                if (!self::$cache['class_has_property'][$identifier]) {
                    continue;
                }

                $type = (self::$cache['property_type'][$identifier] ??= $this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($name)));

                try {
                    $object->{$name} = $property['value_provider']($type);
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

        return $this->deserializeMixed($dataOrResource, $context);
    }
}
