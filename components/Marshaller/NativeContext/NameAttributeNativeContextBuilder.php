<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\Context;

final class NameAttributeNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        if (!class_exists($type)) {
            return $nativeContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                $formatterName = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $name = $attribute->newInstance()->name;

                $nativeContext['symfony']['property_name_formatter'][$formatterName] = static function () use ($name): string {
                    return $name;
                };

                break;
            }
        }

        return $nativeContext;
    }
}
