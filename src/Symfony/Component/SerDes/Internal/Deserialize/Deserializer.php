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
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @template T of mixed
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
     * @param T                    $data
     * @param array<string, mixed> $context
     */
    abstract protected function deserializeScalar(mixed $data, Type $type, array $context): mixed;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     */
    abstract protected function deserializeEnum(mixed $data, Type $type, array $context): mixed;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     *
     * @return \Iterator<mixed>|null
     */
    abstract protected function deserializeList(mixed $data, Type $type, array $context): ?\Iterator;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string, mixed>|null
     */
    abstract protected function deserializeDict(mixed $data, Type $type, array $context): ?\Iterator;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     *
     * @return \Iterator<string, mixed>|array<string, mixed>|null
     */
    abstract protected function deserializeObjectProperties(mixed $data, Type $type, array $context): \Iterator|array|null;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     *
     * @return T
     */
    abstract protected function deserializeMixed(mixed $data, Type $type, array $context): mixed;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     *
     * @return callable(): mixed
     */
    abstract protected function propertyValueCallable(Type|UnionType $type, mixed $data, mixed $value, array $context): callable;

    /**
     * @param T                    $data
     * @param array<string, mixed> $context
     */
    final public function deserialize(mixed $data, Type|UnionType $type, array $context): mixed
    {
        if ($type instanceof UnionType) {
            if (!isset($context['union_selector'][$typeString = (string) $type])) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = (self::$cache['type'][$typeString] ??= TypeFactory::createFromString($context['union_selector'][$typeString]));
        }

        if ($type->isScalar()) {
            $scalar = $this->deserializeScalar($data, $type, $context);

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
            $enum = $this->deserializeEnum($data, $type, $context);

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
            if ($type->isList()) {
                $collection = $this->deserializeList($data, $type, $context);
            } elseif ($type->isDict()) {
                $collection = $this->deserializeDict($data, $type, $context);
            } else {
                $collection = $this->deserializeMixed($data, $type, $context);
            }

            if (null === $collection) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            return $type->isIterable() ? $collection : iterator_to_array($collection);
        }

        if ($type->isObject()) {
            try {
                $className = $type->className();
            } catch (LogicException) {
                $object = new \stdClass();
                foreach ($this->deserializeMixed($data, $type, $context) as $property => $value) {
                    $object->{$property} = $value;
                }

                return $object;
            }

            $objectProperties = $this->deserializeObjectProperties($data, $type, $context);

            if (null === $objectProperties) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            if (null !== $hook = $context['hooks']['deserialize'][$className] ?? $context['hooks']['deserialize']['object'] ?? null) {
                /** @var array{type?: string, context?: array<string, mixed>} $hookResult */
                $hookResult = $hook((string) $type, $context);

                if (isset($hookResult['type'])) {
                    /** @var Type $type */
                    $type = (self::$cache['type'][$hookResult['type']] ??= TypeFactory::createFromString($hookResult['type']));
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
                        fn (string $type, array $context) => $this->propertyValueCallable(self::$cache['type'][$type] ??= TypeFactory::createFromString($type), $data, $value, $context)(),
                        $context,
                    );

                    $name = $hookResult['name'] ?? $name;
                    $context = $hookResult['context'] ?? $context;
                }

                self::$cache['class_has_property'][$identifier = $typeString.$name] ??= $reflection->hasProperty($name);

                if (!self::$cache['class_has_property'][$identifier]) {
                    continue;
                }

                if (isset($hookResult['value_provider'])) {
                    $valueCallables[$name] = $hookResult['value_provider'];

                    continue;
                }

                self::$cache['property_type'][$identifier] ??= TypeFactory::createFromString($this->reflectionTypeExtractor->extractFromProperty($reflection->getProperty($name)));

                $valueCallables[$name] = $this->propertyValueCallable(self::$cache['property_type'][$identifier], $data, $value, $context);
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

        return $this->deserializeMixed($data, $type, $context);
    }
}
