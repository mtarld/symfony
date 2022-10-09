<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\Attribute\PropertyAttributeResolver;
use Symfony\Component\Marshaller\Metadata\NameConverter\PropertyNameConverterInterface;

final class PropertyMetadataFactory
{
    public function __construct(
        private readonly ValueMetadataFactory $valueMetadataFactory,
        private readonly PropertyAttributeResolver $attributeResolver,
        private readonly PropertyNameConverterInterface $nameConverter,
    ) {
    }

    public function forProperty(\ReflectionProperty $property, Context $context, array $factoryContext = []): PropertyMetadata
    {
        $name = $property->getName();
        $attributes = $this->attributeResolver->resolve($property);

        return new PropertyMetadata(
            name: $name,
            convertedName: $this->nameConverter->convert($name, $attributes),
            value: $this->valueMetadataFactory->forProperty($property, $attributes, $context, $factoryContext),
            isPublic: $property->isPublic(),
            attributes: $attributes,
        );
    }
}
