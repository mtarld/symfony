<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

/**
 * @internal
 */
final class HookExtractor
{
    /**
     * @param array<string, mixed> $context
     */
    public function extractFromProperty(\ReflectionProperty $property, array $context): ?callable
    {
        $hookNames = [
            sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName()),
            'property',
        ];

        if (null === $hook = $this->findHook($hookNames, $context)) {
            return null;
        }

        $reflection = new \ReflectionFunction($hook);

        if (4 !== \count($reflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have exactly 4 arguments.', $hookName));
        }

        $propertyParameterType = $reflection->getParameters()[0]->getType();
        if (!$propertyParameterType instanceof \ReflectionNamedType || \ReflectionProperty::class !== $propertyParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "%s" for first argument.', $hookName, \ReflectionProperty::class));
        }

        $accessorParameterType = $reflection->getParameters()[1]->getType();
        if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for second argument.', $hookName));
        }

        $formatParameterType = $reflection->getParameters()[2]->getType();
        if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for third argument.', $hookName));
        }

        $contextParameterType = $reflection->getParameters()[3]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have an "array" for fourth argument.', $hookName));
        }

        return $hook;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function extractFromFunction(\ReflectionFunction $function, array $context): ?callable
    {
        $hookNames = [
            sprintf('%s::%s()', $function->getClosureScopeClass()->getName(), $function->getName()),
            'function',
        ];

        if (null === $hook = $this->findHook($hookNames, $context)) {
            return null;
        }

        $reflection = new \ReflectionFunction($hook);

        if (4 !== \count($reflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have exactly 4 arguments.', $hookName));
        }

        $functionParameterType = $reflection->getParameters()[0]->getType();
        if (!$functionParameterType instanceof \ReflectionNamedType || \ReflectionFunction::class !== $functionParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "%s" for first argument.', $hookName, \ReflectionFunction::class));
        }

        $accessorParameterType = $reflection->getParameters()[1]->getType();
        if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for second argument.', $hookName));
        }

        $formatParameterType = $reflection->getParameters()[2]->getType();
        if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for third argument.', $hookName));
        }

        $contextParameterType = $reflection->getParameters()[3]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have an "array" for fourth argument.', $hookName));
        }

        return $hook;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function extractFromType(Type $type, array $context): ?callable
    {
        $hookNames = [$type->name()];

        if ($type->isNullable()) {
            $hookNames[] = '?'.$type->name();
        }

        if ($type->isObject()) {
            if ($type->isNullable()) {
                array_unshift($hookNames, '?'.$type->className());
            }

            array_unshift($hookNames, $type->className());
        }

        if (null === $hook = $this->findHook($hookNames, $context)) {
            return null;
        }

        if (4 !== \count($reflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have exactly 4 arguments.', $hookName));
        }

        $typeParameterType = $reflection->getParameters()[0]->getType();
        if (!$typeParameterType instanceof \ReflectionNamedType || 'string' !== $typeParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for first argument.', $hookName));
        }

        $accessorParameterType = $reflection->getParameters()[1]->getType();
        if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for second argument.', $hookName));
        }

        $formatParameterType = $reflection->getParameters()[2]->getType();
        if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have a "string" for third argument.', $hookName));
        }

        $contextParameterType = $reflection->getParameters()[3]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" must have an "array" for fourth argument.', $hookName));
        }

        return $hook;
    }

    /**
     * @param list<string>         $hookNames
     * @param array<string, mixed> $context
     */
    private function findHook(array $hookNames, array $context): ?callable
    {
        foreach ($hookNames as $hookName) {
            if (null !== ($hook = $context['hooks'][$hookName] ?? null)) {
                return $hook;
            }
        }

        return null;
    }
}
