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
        $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

        if (!isset($context['symfony']['type_extractor'])) {
            throw new \RuntimeException("Missing \"\$context['symfony']['type_extractor']\".");
        }

        if (!$property->isPublic()) {
            throw new \RuntimeException(sprintf('"%s::$%s" must be public', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        $name = $this->propertyName($property, $propertyIdentifier, $format, $context);
        $type = $this->propertyType($property, $propertyIdentifier, $context);
        $accessor = $this->propertyAccessor($propertyIdentifier, $accessor, $format, $context);

        return $context['property_name_template_generator']($name, $context).$context['property_value_template_generator']($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyName(\ReflectionProperty $property, string $propertyIdentifier, string $format, array $context): string
    {
        $name = sprintf("'%s'", $property->getName());

        if (!isset($context['symfony']['property_name_formatter'][$propertyIdentifier])) {
            return $name;
        }

        return sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s\'](%s, \'%s\', $context)', $propertyIdentifier, $name, $format);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyType(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        if (null !== $type = ($context['symfony']['property_type'][$propertyIdentifier] ?? null)) {
            return $type;
        }

        if (null !== $formatter = ($context['symfony']['property_value_formatter'][$propertyIdentifier] ?? null)) {
            return $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction(\Closure::fromCallable($formatter)));
        }

        return $context['symfony']['type_extractor']->extractFromProperty($property);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyAccessor(string $propertyIdentifier, string $accessor, string $format, array $context): string
    {
        if (!isset($context['symfony']['property_value_formatter'][$propertyIdentifier])) {
            return $accessor;
        }

        return sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s\'](%s, \'%s\', $context)', $propertyIdentifier, $accessor, $format);
    }
}
