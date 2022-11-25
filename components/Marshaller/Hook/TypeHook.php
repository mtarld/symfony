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

        return $context['type_template_generator']($accessorType, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(string $type, array $context): string
    {
        if (null === $formatter = ($context['symfony']['type_formatter'][$type] ?? null)) {
            return $type;
        }

        return $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, string $accessor, array $context): string
    {
        if (null === $formatter = ($context['symfony']['type_formatter'][$type] ?? null)) {
            return $accessor;
        }

        $formatterReflection = new \ReflectionFunction($formatter);

        if (($returnType = $formatterReflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Return type of type formatter "%s" must not be "void" nor "never".', $type));
        }

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of type formatter "%s" must be an array.', $type));
            }
        }

        $isMethod = $formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName());

        if ($isMethod && $formatterReflection->isStatic()) {
            return sprintf('%s::%s(%s, $context)', $formatterReflection->getClosureScopeClass()->getName(), $formatterReflection->getName(), $accessor);
        }

        if (!$isMethod && !str_contains($formatterReflection->getName(), '{closure}')) {
            return sprintf('%s(%s, $context)', $formatterReflection->getName(), $accessor);
        }

        throw new \InvalidArgumentException(sprintf('Type formatter "%s" must be either a non anonymous function or a static method.', $type));
    }
}
