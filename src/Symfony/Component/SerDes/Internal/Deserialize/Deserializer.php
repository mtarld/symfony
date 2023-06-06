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
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\UnionType;

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
     * @return \Iterator<string, mixed>|array<string, mixed>|null
     */
    abstract protected function deserializeObjectProperties(mixed $dataOrResource, Type $type, array $context): \Iterator|array|null;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function deserializeMixed(mixed $dataOrResource, array $context): mixed;

    /**
     * @param array<string, mixed> $context
     *
     * @return callable(): mixed
     */
    abstract protected function propertyValueCallable(Type|UnionType $type, mixed $dataOrResource, mixed $value, array $context): callable;

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    abstract public function deserialize(mixed $resource, Type|UnionType $type, array $context): mixed;

    /**
     * @param array<string, mixed> $context
     */
    protected function doDeserialize(mixed $dataOrResource, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
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
            $objectProperties = $this->deserializeObjectProperties($dataOrResource, $type, $context);

            if (null === $objectProperties) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            if (null !== $hook = $context['hooks']['deserialize'][$className] ?? $context['hooks']['deserialize']['object'] ?? null) {
                /** @var array{type?: Type|UnionType|string, context?: array<string, mixed>} $hookResult */
                $hookResult = $hook($type, $context);

                if (isset($hookResult['type'])) {
                    /** @var Type $type */
                    $type = \is_string($hookResult['type'])
                        ? (self::$cache['type'][$hookResult['type']] ??= TypeFactory::createFromString($hookResult['type']))
                        : $hookResult['type'];
                }

                $context = $hookResult['context'] ?? $context;
            }

            /** @var \ReflectionClass<object> $reflection */
            $reflection = (self::$cache['class_reflection'][$typeString = (string) $type] ??= new \ReflectionClass($className));

            /** @var array<string, callable(): mixed> $valueCallables */
            $valueCallables = [];

            foreach ($objectProperties as $name => $value) {
                if (null !== $hook = $context['hooks']['deserialize'][$className.'['.$name.']'] ?? $context['hooks']['deserialize']['property'] ?? null) {
                    $hookResult = $hook(
                        $reflection,
                        $name,
                        function (Type|UnionType|string $type, array $context) use ($dataOrResource, $value) {
                            /** @var Type $type */
                            $type = \is_string($type) ? (self::$cache['type'][$type] ??= TypeFactory::createFromString($type)) : $type;

                            return $this->propertyValueCallable($type, $dataOrResource, $value, $context)();
                        },
                        $context,
                    );

                    $name = $hookResult['name'] ?? $name;
                    $context = $hookResult['context'] ?? $context;

                    if (\array_key_exists('value_provider', $hookResult) && null === $hookResult['value_provider']) {
                        continue;
                    }
                }

                self::$cache['class_has_property'][$identifier = $typeString.$name] ??= $reflection->hasProperty($name);

                if (!self::$cache['class_has_property'][$identifier]) {
                    continue;
                }

                if (isset($hookResult['value_provider'])) {
                    $valueCallables[$name] = $hookResult['value_provider'];

                    continue;
                }

                self::$cache['property_type'][$identifier] ??= $this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($name));

                $valueCallables[$name] = $this->propertyValueCallable(self::$cache['property_type'][$identifier], $dataOrResource, $value, $context);
            }

            if (isset($context['instantiator'])) {
                return $context['instantiator']($reflection, $valueCallables, $context);
            }

            $object = new $className();

            foreach ($valueCallables as $name => $callable) {
                try {
                    $object->{$name} = $callable();
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
