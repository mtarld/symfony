<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\TypeExtractor;
use Symfony\Component\Marshaller\Type\UnionTypeChecker;

final class ObjectHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function __construct(
        private readonly TypeExtractor $typeExtractor,
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
        $typeExtractor = $this->typeExtractor;

        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($format, $typeExtractor): string {
            $types = $typeExtractor->extract($property);
            if (!UnionTypeChecker::isHomogenousObject($types)) {
                throw new \RuntimeException(sprintf('Type of "%s::$%s" is not homogenous.', $property->getDeclaringClass()->getName(), $property->getName()));
            }

            $value = ValueTemplateGenerator::generate($types[0], sprintf('%s->%s', $objectAccessor, $property->getName()), $format, $context);
            if ('' === $value) {
                return $value;
            }

            return $context['propertyNameGenerator']($property, $context).$value;
        };
    }
}
