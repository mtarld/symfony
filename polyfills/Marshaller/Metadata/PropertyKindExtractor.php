<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

/**
 * @internal
 */
final class PropertyKindExtractor
{
    public const KIND_SCALAR = 'scalar';
    public const KIND_OBJECT = 'object';

    public static function extract(\ReflectionProperty $property): string
    {
        $type = $property->getType();
        if (null === $type) {
            throw new \RuntimeException(sprintf('Cannot retrieve type of "%1$s::$%2$s" property. Please use a "%1$s::$%2$s" hook.', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        if ($type instanceof \ReflectionUnionType) {
            $kinds = array_map(self::extractFromType(...), $type->getTypes());
            if (\count(array_unique($kinds)) > 1) {
                throw new \RuntimeException(sprintf('Union type "%s" of "%s::$%s" property is not homogenous. Please use whether a "%1$s" or a "%2$s::$%3$s hook.', $type, $property->getDeclaringClass()->getName(), $property->getName()));
            }

            return $kinds[0];
        }

        if (null !== $kind = self::extractFromType($type)) {
            return $kind;
        }

        throw new \RuntimeException(sprintf('Type "%s" of "%s::$%s" property is not handled. Please use whether a "%1$s" or a "%2$s::$%3$s hook.', $type, $property->getDeclaringClass()->getName(), $property->getName()));
    }

    private static function extractFromType(\ReflectionNamedType $type): ?string
    {
        if (in_array($type->getName(), ['int', 'float', 'string', 'bool'], true)) {
            return self::KIND_SCALAR;
        }

        if (!$type->isBuiltin()) {
            return self::KIND_OBJECT;
        }

        return null;
    }
}
