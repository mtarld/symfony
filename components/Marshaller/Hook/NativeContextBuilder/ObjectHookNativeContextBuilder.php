<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\Type;

final class ObjectHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        if (!isset($nativeContext['hooks'])) {
            $nativeContext['hooks'] = [];
        }

        $nativeContext['hooks']['object'] = $this->createHook();

        return $nativeContext;
    }

    private function createHook(): callable
    {
        return static function (\ReflectionProperty $property, string $objectAccessor, array $context): string {
            $value = ValueTemplateGenerator::generate(Type::createFromReflection($property), sprintf('%s->%s', $objectAccessor, $property->getName()), $context);
            if ('' === $value) {
                return $value;
            }

            return $context['propertyNameGenerator']($property, $context).$value;
        };
    }
}
