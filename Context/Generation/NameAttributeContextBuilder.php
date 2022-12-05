<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Generation;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;

final class NameAttributeContextBuilder implements GenerationContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        if (!class_exists($type)) {
            return $rawContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Name $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $rawContext['symfony']['marshal']['property_name'][$propertyIdentifier] = $attributeInstance->name;

                break;
            }
        }

        return $rawContext;
    }
}
