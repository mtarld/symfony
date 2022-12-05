<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext\Generation;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\GenerationNativeContextBuilderInterface;

final class NameAttributeNativeContextBuilder implements GenerationNativeContextBuilderInterface
{
    public function build(string $type, Context $context, array $nativeContext): array
    {
        if (!class_exists($type)) {
            return $nativeContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Name $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $nativeContext['symfony']['marshal']['property_name'][$propertyIdentifier] = $attributeInstance->name;

                break;
            }
        }

        return $nativeContext;
    }
}
