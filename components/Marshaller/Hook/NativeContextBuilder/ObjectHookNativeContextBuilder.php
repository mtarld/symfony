<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\TypesExtractor;

final class ObjectHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function __construct(
        private readonly TypesExtractor $typesExtractor,
    ) {
    }

    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        if (!isset($nativeContext['hooks'])) {
            $nativeContext['hooks'] = [];
        }

        $nativeContext['hooks']['object'] = $this->createHook($format);

        return $nativeContext;
    }

    private function createHook(string $format): callable
    {
        $typesExtractor = $this->typesExtractor;

        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($format, $typesExtractor): string {
            $accessor = sprintf('%s->%s', $objectAccessor, $property->getName());
            $types = $typesExtractor->extract($property, $property->getDeclaringClass());

            $value = ValueTemplateGenerator::generate($types, $accessor, $format, $context);
            if ('' === $value) {
                return $value;
            }

            return $context['propertyNameGenerator']($property, $context).$value;
        };
    }
}
