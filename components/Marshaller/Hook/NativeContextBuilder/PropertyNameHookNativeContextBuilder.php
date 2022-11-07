<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;

final class PropertyNameHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function forTemplateGeneration(\ReflectionClass $class, string $format, Context $context, array $nativeContext): array
    {
        if (!isset($nativeContext['hooks'])) {
            $nativeContext['hooks'] = [];
        }

        $properties = $class->getProperties();
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                $nativeContext['hooks'][sprintf('%s::$%s', $class->getName(), $property->getName())] = $this->createHook($attribute->newInstance()->name, $format);
            }
        }

        return $nativeContext;
    }

    private function createHook(string $name, string $format): callable
    {
        if ('json' === $format) {
            return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($name): string {
                $name = $context['fwrite'](sprintf("'%s%s:'", $context['prefix'], json_encode($name)), $context);
                $value = $context['propertyValueGenerator']($property, $objectAccessor, $context);

                return $name.$value;
            };
        }

        throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format));
    }
}
