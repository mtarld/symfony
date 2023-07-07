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

use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;
use Symfony\Component\Serializer\Deserialize\Decoder\DecoderInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Deserialize\Splitter\SplitterInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class LazyUnmarshaller implements UnmarshallerInterface
{
    public function __construct(
        private readonly DecoderInterface $decoder,
        private readonly SplitterInterface $listSplitter,
        private readonly SplitterInterface $dictSplitter,
        private readonly PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private readonly InstantiatorInterface $instantiator,
    ) {
    }

    public function unmarshal(mixed $resource, Type $type, Configuration $configuration, array $context): mixed
    {
        if ($type->isUnion()) {
            // TODO
            // $selectedType = ($context['union_selector'][$typeString = (string) $type] ?? null);
            // if (null === $selectedType) {
            //     throw new UnexpectedValueException(sprintf('Cannot guess type to use for "%s", you may specify a type in "$context[\'union_selector\'][\'%1$s\']".', (string) $type));
            // }
            //
            // /** @var Type $type */
            // $type = \is_string($selectedType) ? Type::createFromString($selectedType) : $selectedType;
        }

        if ($type->isScalar()) {
            $scalar = $this->decoder->decode($resource, $context['offset'], $context['length'], $configuration);

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
            $enum = $this->decoder->decode($resource, $context['offset'], $context['length'], $configuration);

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
                if (null !== $boundaries = $this->listSplitter->split($resource, $type, $context['offset'], $context['length'])) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $configuration, $context);
                }
            } elseif ($type->isDict()) {
                if (null !== $boundaries = $this->dictSplitter->split($resource, $type, $context['offset'], $context['length'])) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $configuration, $context);
                }
            } else {
                $collection = $this->decoder->decode($resource, $context['offset'], $context['length'], $configuration);
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
                return (object) ($this->decoder->decode($resource, $context['offset'], $context['length'], $configuration));
            }

            $boundaries = $this->dictSplitter->split($resource, $type, $context['offset'], $context['length']);

            if (null === $boundaries) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $className = $type->className();
            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $configuration, $context);

            $properties = [];
            foreach ($boundaries as $name => $boundary) {
                if (!isset($propertiesMetadata[$name])) {
                    continue;
                }

                $propertyName = $propertiesMetadata[$name]->name();

                $context['offset'] = $boundary[0];
                $context['length'] = $boundary[1];

                $unmarshal = fn (Type $type) => $this->unmarshal($resource, $type, $configuration, $context);

                $properties[$propertyName] = fn () => $propertiesMetadata[$name]->valueProvider()($unmarshal);
            }

            return $this->instantiator->instantiate($className, $properties);
        }

        return $this->decoder->decode($resource, $context['offset'], $context['length'], $configuration);
    }

    /**
     * @param resource                         $resource
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     * @param array<string, mixed>             $context
     *
     * @return \Iterator<int|string, mixed>
     */
    private function deserializeCollectionItems(mixed $resource, \Iterator $boundaries, Type $type, Configuration $configuration, array $context): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            $context['offset'] = $boundary[0];
            $context['length'] = $boundary[1];

            yield $key => $this->unmarshal($resource, $type, $configuration, $context);
        }
    }
}
