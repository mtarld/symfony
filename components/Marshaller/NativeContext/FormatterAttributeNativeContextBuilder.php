<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\Context;

final class FormatterAttributeNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        if (!class_exists($type)) {
            return $nativeContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                $formatterName = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

                $callable = $attribute->newInstance()->callable;

                $nativeContext['symfony']['property_value_formatter'][$formatterName] = $callable;

                break;
            }
        }

        return $nativeContext;
    }
}
