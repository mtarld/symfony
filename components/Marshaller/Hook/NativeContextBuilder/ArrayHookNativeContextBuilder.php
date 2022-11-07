<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\TypeExtractor;
use Symfony\Component\Marshaller\Type\UnionTypeChecker;

final class ArrayHookNativeContextBuilder implements TemplateGenerationNativeContextBuilderInterface
{
    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        $typeExtractor = new TypeExtractor();

        $nativeContext['hooks']['array'] = static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($typeExtractor): string {
            $types = $typeExtractor->extract($property);

            foreach ($types as $type) {
                if (false === $type->isCollection()) {
                    throw new \RuntimeException(sprintf('Type "%s" of "%s::$%s" property type is not a collection.', $type->name(), $property->getDeclaringClass()->getName(), $property->getName()));
                }
            }

            if (!UnionTypeChecker::isHomogenousCollection($types)) {
                throw new \RuntimeException(sprintf('Type of "%s::$%s" property is not homogenous (some types are lists, others are dicts).', $property->getDeclaringClass()->getName(), $property->getName()));
            }

            $accessor = sprintf('%s->%s', $objectAccessor, $property->getName());

            $template = $context['propertyNameGenerator']($property, $context);

            if ($type->isNullable()) {
                $template .= $context['writeLine']("if (null === $accessor) {", $context);

                ++$context['indentation_level'];
                $template .= $context['fwrite']("'null'", $context);

                --$context['indentation_level'];
                $template .= $context['writeLine']('} else {', $context);

                ++$context['indentation_level'];
            }

            $value = ValueTemplateGenerator::generate($types[0], $accessor, $context);
            if ('' === $value) {
                return '';
            }

            $template .= $value;

            if ($type->isNullable()) {
                --$context['indentation_level'];
                $template .= $context['writeLine']('}', $context);
            }

            return $template;
        };

        return $nativeContext;
    }
}
