<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\Context;

final class FormatterAttributeNativeContextBuilder implements GenerateNativeContextBuilderInterface
{
    public function buildGenerateNativeContext(string $type, Context $context, array $nativeContext): array
    {
        if (!class_exists($type)) {
            return $nativeContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $nativeContext['symfony']['property_formatter'][$propertyIdentifier] = $attribute->newInstance()->formatter;

                break;
            }
        }

        return $nativeContext;
    }
}
