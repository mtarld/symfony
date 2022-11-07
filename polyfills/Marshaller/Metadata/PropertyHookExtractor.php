<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

/**
 * @internal
 */
final class PropertyHookExtractor
{
    /**
     * @param array<string, mixed> $context
     */
    public static function extract(\ReflectionProperty $property, array $context): ?callable
    {
        foreach (self::findHookNames($property) as $hookName) {
            if (null !== ($hook = $context['hooks'][$hookName] ?? null)) {
                return $hook;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function findHookNames(\ReflectionProperty $property): array
    {
        $hooks = [sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName())];

        if (null === $type = $property->getType()) {
            return $hooks;
        }

        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        foreach ($types as $type) {
            $hooks[] = $type->getName();
            if (!$type->isBuiltin()) {
                $hooks[] = 'object';
            }
        }

        return $hooks;
    }
}
