<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

/**
 * @internal
 */
final class PropertyHook
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(\ReflectionProperty $property, string $accessor, string $format, array $context): string
    {
        $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

        if (!$property->isPublic()) {
            throw new \RuntimeException(sprintf('"%s::$%s" must be public', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        $propertyFormatter = isset($context['symfony']['property_formatter'][$propertyIdentifier])
            ? new \ReflectionFunction($context['symfony']['property_formatter'][$propertyIdentifier])
            : null;

        $name = $this->propertyName($property, $propertyIdentifier, $context);
        $type = $this->propertyType($property, $propertyFormatter, $context);
        $accessor = $this->propertyAccessor($propertyIdentifier, $propertyFormatter, $accessor, $context);

        $context['symfony']['current_property_class'] = $property->getDeclaringClass()->getName();

        return $context['property_name_template_generator']($name, $context).$context['property_value_template_generator']($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyName(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        $name = $property->getName();

        if (isset($context['symfony']['property_name'][$propertyIdentifier])) {
            $name = addslashes($context['symfony']['property_name'][$propertyIdentifier]);
        }

        return sprintf("'%s'", $name);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyType(\ReflectionProperty $property, ?\ReflectionFunction $propertyFormatter, array $context): string
    {
        return null !== $propertyFormatter
            ? $this->typeExtractor->extractFromReturnType($propertyFormatter)
            : $this->typeExtractor->extractFromProperty($property);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyAccessor(string $propertyIdentifier, ?\ReflectionFunction $propertyFormatter, string $accessor, array $context): string
    {
        if (null === $propertyFormatter) {
            return $accessor;
        }

        if (!$propertyFormatter->getClosureScopeClass()?->hasMethod($propertyFormatter->getName()) || !$propertyFormatter->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', $propertyIdentifier));
        }

        if (($returnType = $propertyFormatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', $propertyIdentifier));
        }

        if (2 !== \count($propertyFormatter->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Property formatter "%s" must have exactly two parameters.', $propertyIdentifier));
        }

        if (null !== ($contextParameter = $propertyFormatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', $propertyIdentifier));
            }
        }

        return sprintf('%s::%s(%s, $context)', $propertyFormatter->getClosureScopeClass()->getName(), $propertyFormatter->getName(), $accessor);
    }
}
