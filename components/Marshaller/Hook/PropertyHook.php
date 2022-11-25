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
            throw new \RuntimeException('Missing "$context[\'symfony\'][\'type_extractor\']".');
        }

        if (!$property->isPublic()) {
            throw new \RuntimeException(sprintf('"%s::$%s" must be public', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        $name = $this->propertyName($property, $propertyIdentifier, $context);
        $type = $this->propertyType($property, $propertyIdentifier, $context);
        $accessor = $this->propertyAccessor($propertyIdentifier, $accessor, $context);

        return $context['property_name_template_generator']($name, $context).$context['property_value_template_generator']($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyName(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        $name = sprintf("'%s'", $property->getName());

        if (isset($context['symfony']['property_name'][$propertyIdentifier])) {
            return sprintf("'%s'", $context['symfony']['property_name'][$propertyIdentifier]);
        }

        if (!isset($context['symfony']['property_name_formatter'][$propertyIdentifier])) {
            return $name;
        }

        $formatterReflection = new \ReflectionFunction($context['symfony']['property_name_formatter'][$propertyIdentifier]);

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of property name formatter "%s" must be an array.', $propertyIdentifier));
            }
        }

        $isAnonymous = str_contains($formatterReflection->getName(), '{closure}');
        $isMethod = !$isAnonymous && $formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName());

        if ($isAnonymous || ($isMethod && !$formatterReflection->isStatic())) {
            return sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s\'](%s, $context)', $propertyIdentifier, $name);
        }

        $callable = sprintf('%s(%s, $context)', $formatterReflection->getName(), $name);
        if (null !== $declaringClass = $declaringClass = $formatterReflection->getClosureScopeClass()) {
            $callable = sprintf('%s::%s', $declaringClass->getName(), $callable);
        }

        return $callable;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyType(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        if (null !== $formatter = ($context['symfony']['property_value_formatter'][$propertyIdentifier] ?? null)) {
            return $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter));
        }

        if (null !== $type = ($context['symfony']['property_type'][$propertyIdentifier] ?? null)) {
            return $type;
        }

        return $context['symfony']['type_extractor']->extractFromProperty($property);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyAccessor(string $propertyIdentifier, string $accessor, array $context): string
    {
        if (null === $formatter = ($context['symfony']['property_value_formatter'][$propertyIdentifier] ?? null)) {
            return $accessor;
        }

        $formatterReflection = new \ReflectionFunction($formatter);

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of property value formatter "%s" must be an array.', $propertyIdentifier));
            }
        }

        $isAnonymous = str_contains($formatterReflection->getName(), '{closure}');
        $isMethod = !$isAnonymous && $formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName());

        if ($isAnonymous || ($isMethod && !$formatterReflection->isStatic())) {
            return sprintf('$context[\'symfony\'][\'property_value_formatter\'][\'%s\'](%s, $context)', $propertyIdentifier, $accessor);
        }

        $callable = sprintf('%s(%s, $context)', $formatterReflection->getName(), $accessor);
        if (null !== $declaringClass = $declaringClass = $formatterReflection->getClosureScopeClass()) {
            $callable = sprintf('%s::%s', $declaringClass->getName(), $callable);
        }

        return $callable;
    }
}
