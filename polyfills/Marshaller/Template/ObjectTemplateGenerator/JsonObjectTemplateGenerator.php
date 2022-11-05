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
        $template = '';
        $context['classes'][] = $class->getName();
        $context['prefix'] = '{';

        $objectName = '$'.uniqid('o');

        $template .= self::writeStatement("$objectName = $accessor", $context);

        foreach ($class->getProperties() as $property) {
            if (null !== $hook = PropertyHookExtractor::extract($property, $context)) {
                $hookContext = $context + [
                    'propertyNameGenerator' => self::generatePropertyName(...),
                    'propertyValueGenerator' => self::generatePropertyValue(...),
                    'fwrite' => self::fwrite(...),
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
        $template .= self::writeStatement("unset($objectName)", $context);

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

        if (PropertyKindExtractor::KIND_SCALAR === $propertyKind) {
            return self::fwrite("json_encode($propertyAccessor)", $context);
        }

        if (PropertyKindExtractor::KIND_OBJECT === $propertyKind) {
            ++$context['depth'];

            if ($context['depth'] > $context['max_depth']) {
                return null;
            }

            $className = $property->getType()->getName();
            if (isset($context['classes'][$className]) && $context['reject_circular_reference']) {
                throw new \RuntimeException(sprintf('Circular reference on "%s" detected.', $className));
            }

            return self::generate(new \ReflectionClass($className), $propertyAccessor, $context);
        }

        throw new \LogicException(sprintf('Unexpected "%s" property kind', $propertyKind));
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function fwrite(string $content, array $context): string
    {
        return self::writeStatement("fwrite(\$resource, $content)", $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function writeStatement(string $statement, array $context): string
    {
        return sprintf('%s%s;%s', str_repeat(' ', 4 * $context['indentation_level']), $statement, PHP_EOL);
    }
}
