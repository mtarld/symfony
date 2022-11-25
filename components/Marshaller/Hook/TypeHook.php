<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

/**
 * @internal
 */
final class TypeHook
{
    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(string $type, string $accessor, string $format, array $context): string
    {
        if (!isset($context['symfony']['type_extractor'])) {
            throw new \RuntimeException('Missing "$context[\'symfony\'][\'type_extractor\']".');
        }

        $accessor = $this->accessor($type, $accessor, $context);
        $accessorType = $this->type($type, $context);

        return $context['type_value_template_generator']($accessorType, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(string $type, array $context): string
    {
        if (null === $formatter = ($context['symfony']['type_value_formatter'][$type] ?? null)) {
            return $type;
        }

        return $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, string $accessor, array $context): string
    {
        if (null === $formatter = ($context['symfony']['type_value_formatter'][$type] ?? null)) {
            return $accessor;
        }

        $formatterReflection = new \ReflectionFunction($formatter);

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of type value formatter "%s" must be an array.', $type));
            }
        }

        $isAnonymous = str_contains($formatterReflection->getName(), '{closure}');
        $isMethod = !$isAnonymous && $formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName());

        if ($isAnonymous || ($isMethod && !$formatterReflection->isStatic())) {
            return sprintf('$context[\'symfony\'][\'type_value_formatter\'][\'%s\'](%s, $context)', $type, $accessor);
        }

        $callable = sprintf('%s(%s, $context)', $formatterReflection->getName(), $accessor);
        if (null !== $declaringClass = $declaringClass = $formatterReflection->getClosureScopeClass()) {
            $callable = sprintf('%s::%s', $declaringClass->getName(), $callable);
        }

        return $callable;
    }
}
