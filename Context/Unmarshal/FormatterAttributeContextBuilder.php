<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Unmarshal;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\UnmarshalContextBuilderInterface;

final class FormatterAttributeContextBuilder implements UnmarshalContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        if (!class_exists($type)) {
            return $rawContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Formatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $rawContext['symfony']['unmarshal']['property_formatter'][$propertyIdentifier] = $attributeInstance->unmarshalFormatter;

                break;
            }
        }

        return $rawContext;
    }
}
