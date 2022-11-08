<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\TypesExtractor;

final class ArrayHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function __construct(
        private readonly TypesExtractor $typesExtractor,
    ) {
    }

    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        $typesExtractor = $this->typesExtractor;

        $nativeContext['hooks']['array'] = static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($typesExtractor, $format): string {
            $types = $typesExtractor->extract($property, $property->getDeclaringClass());

            if (false === $types->isOnlyCollection()) {
                throw new \RuntimeException(sprintf('Type "%s" of "%s::$%s" property type is not a collection.', $type->name(), $property->getDeclaringClass()->getName(), $property->getName()));
            }

            $accessor = sprintf('%s->%s', $objectAccessor, $property->getName());

            $template = $context['propertyNameGenerator']($property, $context);

            if ($types->isNullable()) {
                $template .= $context['writeLine']("if (null === $accessor) {", $context);

                ++$context['indentation_level'];
                $template .= $context['fwrite']("'null'", $context);

                --$context['indentation_level'];
                $template .= $context['writeLine']('} else {', $context);

                ++$context['indentation_level'];
            }

            $value = ValueTemplateGenerator::generate($types, $accessor, $format, $context);
            if ('' === $value) {
                return '';
            }

            $template .= $value;

            if ($types->isNullable()) {
                --$context['indentation_level'];
                $template .= $context['writeLine']('}', $context);
            }

            return $template;
        };

        return $nativeContext;
    }
}
