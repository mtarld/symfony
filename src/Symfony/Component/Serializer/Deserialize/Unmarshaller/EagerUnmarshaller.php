<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Unmarshaller;

use Symfony\Component\Serializer\Deserialize\Decoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class EagerUnmarshaller implements UnmarshallerInterface
{
    /**
     * @var array{property_type: array<string, Type>, class_reflection: array<string, \ReflectionClass<object>}
     */
    protected static array $cache = [
        'property_type' => [],
        'class_reflection' => [],
    ];

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
        private readonly DecoderInterface $decoder,
    ) {
    }

    public function unmarshal(mixed $resource, Type $type, callable $instantiator, array $context): mixed
    {
        return $this->denormalize($this->decoder->decode($resource, 0, -1, $context), $type, $instantiator, $context);
    }

    /**
     * @param callable(class-string, array<string, callable(): mixed>, array<string, mixed>): object $instantiator
     * @param array<string, mixed> $context
     */
    private function denormalize(mixed $data, Type $type, callable $instantiator, array $context): mixed
    {
        if ($type->isUnion()) {
            $selectedType = ($context['union_selector'][$typeString = (string) $type] ?? null);
            if (null === $selectedType) {
                throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            }

            /** @var Type $type */
            $type = \is_string($selectedType) ? TypeFactory::createFromString($selectedType) : $selectedType;
        }

        if ($type->isScalar()) {
            if (null === $data) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

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

        if ($type->isEnum()) {
            if (null === $data) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            try {
                return ($type->className())::from($data);
            } catch (\ValueError $e) {
                throw new UnexpectedValueException(sprintf('Unexpected "%s" value for "%s" backed enumeration.', $data, $type));
            }
        }

        if ($type->isCollection()) {
            if ($type->isList() || $type->isDict()) {
                if (!\is_array($data)) {
                    throw new UnexpectedValueException(sprintf('Unexpected value for a collection, expected "array", got "%s".', get_debug_type($data)));
                }

                $data = $this->denormalizeCollectionItems($data, $type->collectionValueType(), $instantiator, $context);
            }

            if (null === $data) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            return $type->isIterable() ? $data : iterator_to_array($data);
        }

        if ($type->isObject()) {
            if (!$type->hasClass()) {
                if (!\is_array($data)) {
                    throw new UnexpectedValueException(sprintf('Unexpected value for object, expected "array", got "%s".', get_debug_type($data)));
                }
               
                $object = new \stdClass();
                foreach ($data as $name => $value) {
                    $object->{$name} = $value;
                }

                return $object;
            }

            $className = $type->className();

            /** @var \ReflectionClass<object> $reflection */
            $reflection = (self::$cache['class_reflection'][$className] ??= new \ReflectionClass($className));

            if (null === $data) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $properties = [];
            foreach ($data as $name => $value) {
                $properties[$name] = fn (Type $type = null) => $this->denormalize(
                    $value,
                    $type ?? (self::$cache['property_type'][$className.$name] ??= $this->typeExtractor->extractFromProperty($reflection->getProperty($name))),
                    $instantiator,
                    $context,
                );
            }

            return $instantiator($className, $properties, $context);
        }

        return $data;
    }

    /**
     * @param array<string, mixed>|list<mixed> $collection
     * @param callable(class-string, array<string, callable(): mixed>, array<string, mixed>): object $instantiator
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function denormalizeCollectionItems(array $collection, Type $type, callable $instantiator, array $context): \Iterator
    {
        foreach ($collection as $key => $value) {
            yield $key => $this->denormalize($value, $type, $instantiator, $context);
        }
    }
}
