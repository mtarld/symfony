<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

/**
 * @internal
 */
final class TypeHook
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(string $type, string $accessor, string $format, array $context): string
    {
        $typeFormatter = isset($context['symfony']['type_formatter'][$type]) ? new \ReflectionFunction($context['symfony']['type_formatter'][$type]) : null;

        $accessor = $this->accessor($type, $typeFormatter, $accessor, $context);
        $accessorType = $this->type($type, $typeFormatter, $context);

        return $context['type_template_generator']($accessorType, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(string $type, ?\ReflectionFunction $typeFormatter, array $context): string
    {
        $currentPropertyClass = $context['symfony']['current_property_class'] ?? null;

        if (null !== $typeFormatter) {
            $type = $this->typeExtractor->extractFromReturnType($typeFormatter);
            $declaringClass = $typeFormatter instanceof \ReflectionMethod ? $typeFormatter->getDeclaringClass() : $typeFormatter->getClosureScopeClass();

            // If method doesn't belong to the current class, ignore generic search
            if ($declaringClass->getName() !== $currentPropertyClass) {
                $currentPropertyClass = null;
            }
        }

        if (null !== $currentPropertyClass && isset($context['symfony']['generic_parameter_types'][$currentPropertyClass][$type])) {
            $type = $context['symfony']['generic_parameter_types'][$currentPropertyClass][$type];
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, ?\ReflectionFunction $typeFormatter, string $accessor, array $context): string
    {
        if (null === $typeFormatter) {
            return $accessor;
        }

        if (!$typeFormatter->getClosureScopeClass()?->hasMethod($typeFormatter->getName()) || !$typeFormatter->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Type formatter "%s" must be a static method.', $type));
        }

        if (($returnType = $typeFormatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Return type of type formatter "%s" must not be "void" nor "never".', $type));
        }

        if (2 !== \count($typeFormatter->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Type formatter "%s" must have exactly two parameters.', $type));
        }

        if (null !== ($contextParameter = $typeFormatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of type formatter "%s" must be an array.', $type));
            }
        }

        return sprintf('%s::%s(%s, $context)', $typeFormatter->getClosureScopeClass()->getName(), $typeFormatter->getName(), $accessor);
    }
}
