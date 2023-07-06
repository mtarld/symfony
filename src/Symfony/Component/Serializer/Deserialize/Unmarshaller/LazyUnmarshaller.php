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

use Symfony\Component\Serializer\Deserialize\Configuration;
use Symfony\Component\Serializer\Deserialize\Decoder\DecoderInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Deserialize\Runtime;
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

    public function unmarshal(mixed $resource, Type $type, Configuration $configuration, Runtime $runtime = null): mixed
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
            $scalar = $this->decoder->decode($resource, $runtime->offset, $runtime->length, $configuration);

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
            $enum = $this->decoder->decode($resource, $runtime->offset, $runtime->length, $configuration);

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
                if (null !== $boundaries = $this->listSplitter->split($resource, $type, $runtime->offset, $runtime->length)) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $configuration, $runtime);
                }
            } elseif ($type->isDict()) {
                if (null !== $boundaries = $this->dictSplitter->split($resource, $type, $runtime->offset, $runtime->length)) {
                    $collection = $this->deserializeCollectionItems($resource, $boundaries, $type->collectionValueType(), $configuration, $runtime);
                }
            } else {
                $collection = $this->decoder->decode($resource, $runtime->offset, $runtime->length);
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
                return (object) ($this->decoder->decode($resource, $runtime->offset, $runtime->length));
            }

            $boundaries = $this->dictSplitter->split($resource, $type, $runtime->offset, $runtime->length);

            if (null === $boundaries) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $className = $type->className();
            $propertiesMetadata = $this->propertyMetadataLoader->load($runtime->originalType, $className, $configuration);

            $properties = [];
            foreach ($boundaries as $name => $boundary) {
                if (!isset($propertiesMetadata[$name])) {
                    continue;
                }

                $propertyName = $propertiesMetadata[$name]->name;

                $runtime->offset = $boundary[0];
                $runtime->length = $boundary[1];

                $unmarshal = fn (Type $type) => $this->unmarshal($resource, $type, $configuration, $runtime);

                $properties[$propertyName] = fn () => ($propertiesMetadata[$name]->valueProvider)($unmarshal);
            }

            return $this->instantiator->instantiate($className, $properties);
        }

        return $this->decoder->decode($resource, $runtime->offset, $runtime->length);
    }

    /**
     * @param resource                         $resource
     * @param \Iterator<array{0: int, 1: int}> $boundaries
     *
     * @return \Iterator<int|string, mixed>
     */
    private function deserializeCollectionItems(mixed $resource, \Iterator $boundaries, Type $type, Configuration $configuration, Runtime $runtime): \Iterator
    {
        foreach ($boundaries as $key => $boundary) {
            $runtime->offset = $boundary[0];
            $runtime->length = $boundary[1];

            yield $key => $this->unmarshal($resource, $type, $configuration, $runtime);
        }
    }
}
