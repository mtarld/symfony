<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\ObjectTemplateGenerator;

use Symfony\Polyfill\Marshaller\Metadata\PropertyHookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\PropertyKindExtractor;
use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGeneratorInterface;

/**
 * @internal
 */
final class JsonObjectTemplateGenerator implements ObjectTemplateGeneratorInterface
{
    public static function generate(\ReflectionClass $class, string $accessor, array $context): string
    {
        if (isset($context['classes'][$class->getName()]) && $context['reject_circular_reference']) {
            throw new \RuntimeException(sprintf('Circular reference on "%s" detected.', $class->getName()));
        }

        $context['classes'][$class->getName()] = true;

        if ($context['depth'] > $context['max_depth']) {
            return '';
        }

        $template = '';
        $context['classes'][] = $class->getName();
        $context['prefix'] = '';

        $objectName = '$'.uniqid('o');

        $template .= self::writeLine("$objectName = $accessor;", $context);
        $template .= self::fwrite("'{'", $context);

        foreach ($class->getProperties() as $property) {
            if (null !== $hook = PropertyHookExtractor::extract($property, $context)) {
                $hookContext = $context + [
                    'propertyNameGenerator' => self::generatePropertyName(...),
                    'propertyValueGenerator' => self::generatePropertyValue(...),
                    'fwrite' => self::fwrite(...),
                    'writeLine' => self::writeLine(...),
                ];

                $template .= $hook($property, $objectName, $hookContext);
                $context['prefix'] = ',';

                continue;
            }

            $propertyValue = self::generatePropertyValue($property, $objectName, $context);
            if (null !== $propertyValue) {
                $template .= self::generatePropertyName($property, $context).$propertyValue;
            }

            $context['prefix'] = ',';
        }

        $template .= self::fwrite("'}'", $context);
        $template .= self::writeLine("unset($objectName);", $context);

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generatePropertyName(\ReflectionProperty $property, array $context): string
    {
        return self::fwrite(sprintf("'%s%s:'", $context['prefix'], json_encode($property->getName())), $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generatePropertyValue(\ReflectionProperty $property, string $objectAccessor, array $context): ?string
    {
        $propertyKind = PropertyKindExtractor::extract($property);
        $propertyAccessor = sprintf('%s->%s', $objectAccessor, $property->getName());

        $type = $property->getType();

        if ($type instanceof \ReflectionUnionType) {
            if (\count(array_unique(array_map(fn (\ReflectionNamedType $t): bool => $t->allowsNull(), $type->getTypes()))) > 1) {
                throw new \RuntimeException(sprintf('Union type "%s" of "%s::$%s" property is not homogenous on nullablity. Please use whether a "%1$s" or a "%2$s::$%3$s hook.', $type, $property->getDeclaringClass()->getName(), $property->getName()));
            }

            $type = $type->getTypes()[0];
        }

        $template = '';

        if ($type->allowsNull()) {
            $template .= self::writeLine("if (null === $propertyAccessor) {", $context);

            ++$context['indentation_level'];
            $template .= self::fwrite("'null'", $context);

            --$context['indentation_level'];
            $template .= self::writeLine('} else {', $context);

            ++$context['indentation_level'];
        }

        if (PropertyKindExtractor::KIND_SCALAR === $propertyKind) {
             $template .= self::fwrite("json_encode($propertyAccessor)", $context);
        } elseif (PropertyKindExtractor::KIND_OBJECT === $propertyKind) {
            ++$context['depth'];
            $template .= self::generate(new \ReflectionClass($type->getName()), $propertyAccessor, $context);
        } else {
            throw new \LogicException(sprintf('Unexpected "%s" property kind', $propertyKind));
        }

        if ($type->allowsNull()) {
            --$context['indentation_level'];
            $template .= self::writeLine('}', $context);
        }

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function fwrite(string $content, array $context): string
    {
        return self::writeLine("fwrite(\$resource, $content);", $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function writeLine(string $line, array $context): string
    {
        return str_repeat(' ', 4 * $context['indentation_level']).$line.PHP_EOL;
    }
}
