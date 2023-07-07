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
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class EagerUnmarshaller implements UnmarshallerInterface
{
    public function __construct(
        private readonly DecoderInterface $decoder,
        private readonly PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private readonly InstantiatorInterface $instantiator,
    ) {
    }

    public function unmarshal(mixed $resource, Type $type, Configuration $configuration, array $context): mixed
    {
        return $this->denormalize(
            $this->decoder->decode($resource, 0, -1, $configuration),
            $type,
            $configuration,
            $context ?? [],
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function denormalize(mixed $data, Type $type, Configuration $configuration, array $context): mixed
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
            // TODO set context['type'] as well
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

                $data = $this->denormalizeCollectionItems($data, $type->collectionValueType(), $configuration, $context);
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

                return (object) $data;
            }

            if (null === $data) {
                if (!$type->isNullable()) {
                    throw new UnexpectedValueException(sprintf('Unexpected "null" value for "%s" type.', (string) $type));
                }

                return null;
            }

            $className = $type->className();
            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $configuration, $context);

            $properties = [];
            foreach ($data as $name => $value) {
                if (!isset($propertiesMetadata[$name])) {
                    continue;
                }

                $propertyName = $propertiesMetadata[$name]->name();
                $unmarshal = fn (Type $type) => $this->denormalize($value, $type, $configuration, $context);

                $properties[$propertyName] = fn () => $propertiesMetadata[$name]->valueProvider()($unmarshal);
            }

            return $this->instantiator->instantiate($className, $properties);
        }

        return $data;
    }

    /**
     * @param array<int|string, mixed> $collection
     * @param array<string, mixed>     $context
     *
     * @return \Iterator<mixed>|\Iterator<string, mixed>
     */
    private function denormalizeCollectionItems(array $collection, Type $type, Configuration $configuration, array $context): \Iterator
    {
        foreach ($collection as $key => $value) {
            yield $key => $this->denormalize($value, $type, $configuration, $context);
        }
    }
}
