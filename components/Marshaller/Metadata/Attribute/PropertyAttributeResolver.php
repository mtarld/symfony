<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Attribute;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Groups;
use Symfony\Component\Marshaller\Attribute\Name;

final class PropertyAttributeResolver
{
    private const PROPERTY_ATTRIBUTE_METADATA_CLASSES = [
        Name::class => NameAttribute::class,
        Groups::class => GroupsAttribute::class,
        Formatter::class => FormatterAttribute::class,
    ];

    public function resolve(\ReflectionProperty $property): Attributes
    {
        $attributesMetadata = [];
        foreach ($property->getAttributes() as $attribute) {
            if (null === ($attributeMetadataClass = self::PROPERTY_ATTRIBUTE_METADATA_CLASSES[$attribute->getName()] ?? null)) {
                continue;
            }

            $attributesMetadata[] = new $attributeMetadataClass($attribute);
        }

        return new Attributes($attributesMetadata);
    }
}
