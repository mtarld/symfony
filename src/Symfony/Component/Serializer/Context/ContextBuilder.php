<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHookInterface as DeserializeObjectHookInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHookInterface as SerializeObjectHookInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ContextBuilder
{
    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
        private readonly InstantiatorInterface $lazyObjectInstantiator,
        private readonly SerializeObjectHookInterface $serializeObjectHook,
        private readonly DeserializeObjectHookInterface $deserializeObjectHook,
        private readonly ContainerInterface $serializeHookServices,
        private readonly ContainerInterface $deserializeHookServices,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function build(array $context, bool $isSerialization): array
    {
        $propertyGroups = [];
        $propertyNames = [];
        $propertyFormatters = [];

        $serializeKey = $isSerialization ? 'serialize' : 'deserialize';

        foreach ($this->serializableResolver->resolve() as $className) {
            $propertyGroups += $this->propertyGroups($className);
            $propertyNames += $this->propertyNames($className, $isSerialization);
            $propertyFormatters += $this->propertyFormatters($className, $isSerialization);
        }

        $context['_symfony'][$serializeKey]['property_groups'] = $propertyGroups;
        $context['_symfony'][$serializeKey]['property_name'] = $propertyNames;
        $context['_symfony'][$serializeKey]['property_formatter'] = $propertyFormatters;

        if (!$isSerialization && !\is_callable($instantiator = $context['instantiator'] ?? null)) {
            $context['instantiator'] = match ($instantiator) {
                'eager', null => null,
                'lazy' => $this->lazyObjectInstantiator,
                default => throw new InvalidArgumentException('Context value "instantiator" must be "lazy", "eager", or a valid callable.'),
            };
        }

        $context['hooks'][$serializeKey]['object'] ??= $isSerialization ? $this->serializeObjectHook : $this->deserializeObjectHook;
        $context['services'][$serializeKey] = $isSerialization ? $this->serializeHookServices : $this->deserializeHookServices;

        return $context;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, array<string, true>>>
     */
    private function propertyGroups(string $className): array
    {
        $propertyGroups = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Groups::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Groups $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                foreach ($attributeInstance->groups as $group) {
                    $propertyGroups[$property->getDeclaringClass()->getName()][$property->getName()][$group] = true;
                }

                break;
            }
        }

        return $propertyGroups;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, string>>
     */
    private function propertyNames(string $className, bool $isSerialization): array
    {
        $propertyNames = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (SerializedName::class !== $attribute->getName()) {
                    continue;
                }

                /** @var SerializedName $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                if ($isSerialization) {
                    $propertyNames[$property->getDeclaringClass()->getName()][$property->getName()] = $attributeInstance->name;
                } else {
                    $propertyNames[$property->getDeclaringClass()->getName()][$attributeInstance->name] = $property->getName();
                }

                break;
            }
        }

        return $propertyNames;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, callable>>
     */
    private function propertyFormatters(string $className, bool $isSerialization): array
    {
        $formatterAttributeClassName = $isSerialization ? SerializeFormatter::class : DeserializeFormatter::class;
        $propertyFormatters = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if ($formatterAttributeClassName !== $attribute->getName()) {
                    continue;
                }

                /** @var SerializeFormatter|DeserializeFormatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyFormatters[$property->getDeclaringClass()->getName()][$property->getName()] = $attributeInstance->formatter;

                break;
            }
        }

        return $propertyFormatters;
    }
}
