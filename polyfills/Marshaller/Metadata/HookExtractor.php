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
        $hookNames = [sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName())];
        if (null !== $hook = $this->findHook($hookNames, $context)) {
            return $hook;
        }

        $type = Type::fromReflection($property->getType(), $property->getDeclaringClass());

        return $this->extractFromType($type, $context);
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

        return $this->findHook($hookNames, $context);
    }

    /**
     * @param list<string> $hookNames
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
