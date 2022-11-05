<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

final class PropertyHookExtractor
{
    /**
     * @param array<string, mixed> $context
     */
    public function extract(\ReflectionProperty $property, array $context): ?callable
    {
        foreach ($this->findHookNames($property) as $hookName) {
            if (null !== ($hook = $context['hooks'][$hookName] ?? null)) {
                return $hook;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function findHookNames(\ReflectionProperty $property): array
    {
        $hooks = [sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName())];

        if (null === $type = $property->getType()) {
            return $hooks;
        }

        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        foreach ($types as $type) {
            $hooks[] = $type->getName();
            if ($type->allowsNull()) {
                $hooks[] = '?'.$type->getName();
            }
        }

        return $hooks;
    }
}
