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
            return sprintf("'%s'", addslashes($context['symfony']['property_name'][$propertyIdentifier]));
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyType(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        $formatter = ($context['symfony']['property_formatter'][$propertyIdentifier] ?? null);

        $type = null !== $formatter
            ? $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter))
            : $context['symfony']['type_extractor']->extractFromProperty($property);

        if (isset($context['symfony']['generic_types'][$type])) {
            $type = $context['symfony']['generic_types'][$type];
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyAccessor(string $propertyIdentifier, string $accessor, array $context): string
    {
        if (null === $formatter = ($context['symfony']['property_formatter'][$propertyIdentifier] ?? null)) {
            return $accessor;
        }

        $formatterReflection = new \ReflectionFunction($formatter);

        if (!$formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName()) || !$formatterReflection->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', $propertyIdentifier));
        }

        if (($returnType = $formatterReflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', $propertyIdentifier));
        }

        if (2 !== \count($formatterReflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Property formatter "%s" must have exactly two parameters.', $propertyIdentifier));
        }

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', $propertyIdentifier));
            }
        }

        return sprintf('%s::%s(%s, $context)', $formatterReflection->getClosureScopeClass()->getName(), $formatterReflection->getName(), $accessor);
    }
}
