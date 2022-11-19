<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

/**
 * @internal
 */
final class PropertyHook
{
    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(\ReflectionProperty $property, string $accessor, string $format, array $context): string
    {
        $symfonyContext = $context['symfony'] ?? [];
        $identifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

        if (!isset($symfonyContext['type_extractor'])) {
            throw new \RuntimeException("Missing \"\$context['symfony']['type_extractor']\".");
        }

        if (!$property->isPublic()) {
            throw new \RuntimeException(sprintf('"%s::$%s" must be public', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        $name = sprintf("'%s'", $property->getName());
        if (isset($symfonyContext['property_name_formatter'][$identifier])) {
            $name = sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s\'](%s, $context)', $identifier, $name);
        }

        $type = $symfonyContext['type_extractor']->extractFromProperty($property);
        if (null !== $formatter = ($symfonyContext['property_value_formatter'][$identifier] ?? null)) {
            $accessor = sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s\'](%s, $context)', $identifier, $accessor);
            $type = $symfonyContext['type_extractor']->extractFromReturnType(new \ReflectionFunction(\Closure::fromCallable($formatter)));
        }

        return $context['property_name_template_generator']($name, $context).$context['property_value_template_generator']($type, $accessor, $context);
    }
}
