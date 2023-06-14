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
use Symfony\Component\Serializer\Deserialize\Splitter\SplitterInterface;
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
final class LazyUnmarshaller implements UnmarshallerInterface
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
        private readonly SplitterInterface $listSplitter,
        private readonly SplitterInterface $dictSplitter,
    ) {
    }

    public function unmarshal(mixed $resource, Type $type, callable $instantiator, array $context): mixed
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
            $scalar = $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);

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
            $enum = $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);

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
            $collection = null;

            if ($type->isList()) {
                if (null !== $boundaries = $this->listSplitter->split($resource, $type, $context)) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $instantiator, $context);
                }
            } elseif ($type->isDict()) {
                if (null !== $boundaries = $this->dictSplitter->split($resource, $type, $context)) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $instantiator, $context);
                }
            } else {
                $collection = $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);
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
            if (!$type->hasClass()) {
                $object = new \stdClass();
                $properties = $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);
               
                foreach ($properties as $name => $value) {
                    $object->{$name} = $value;
                }

                return $object;
            }

            $className = $type->className();

            /** @var \ReflectionClass<object> $reflection */
            $reflection = (self::$cache['class_reflection'][$className] ??= new \ReflectionClass($className));
            $boundaries = $this->dictSplitter->split($resource, $type, $context);

            if (null === $boundaries) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $properties = [];
            foreach ($boundaries as $name => $boundary) {
                $properties[$name] = fn () => $this->unmarshal(
                    $resource,
                    self::$cache['property_type'][$className.$name] ??= $this->typeExtractor->extractFromProperty($reflection->getProperty($name)),
                    $instantiator,
                    ['boundary' => $boundary] + $context,
                );
            }

            return $instantiator($className, $properties, $context);
        }

        return $this->decoder->decode($resource, $context['boundary'][0] ?? 0, $context['boundary'][1] ?? -1, $context);
    }

    /**
     * @param resource                         $resource
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param callable(class-string, array<string, callable(): mixed>, array<string, mixed>): object $instantiator
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<int|string, mixed>
     */
    private function deserializeCollectionItems(mixed $resource, \Iterator $boundaries, Type $type, callable $instantiator, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            yield $key => $this->unmarshal($resource, $type, $instantiator, ['boundary' => $boundary] + $context);
        }
    }
}
