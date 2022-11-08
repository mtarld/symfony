<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class ReflectionTypesExtractor
{
    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): ?Types
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection, $declaringClass);
        }

        return $this->extractFromReturnType($reflection, $declaringClass);
    }

    private function extractFromProperty(\ReflectionProperty $property, \ReflectionClass $declaringClass): ?Types
    {
        try {
            if ($types = $this->extractFromType($property->getType(), $declaringClass)) {
                return $types;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    // TODO test
    private function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Types
    {
        try {
            if ($types = $this->extractFromType($function->getReturnType(), $declaringClass)) {
                return $types;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    private function extractFromType(\ReflectionType $type, \ReflectionClass $declaringClass): Types
    {
        $types = [];
        $nullable = $type->allowsNull();

        foreach (($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) ? $type->getTypes() : [$type] as $type) {
            $phpTypeOrClass = $type->getName();

            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
                continue;
            }

            if ('array' === $phpTypeOrClass) {
                $types[] = new Type(name: 'array', isNullable: $type->allowsNull(), isCollection: true);

                continue;
            }
            if ($type->isBuiltin()) {
                $types[] = new Type(name: $phpTypeOrClass, isNullable: $type->allowsNull());

                continue;
            }

            $className = $phpTypeOrClass;

            if ($declaringClass && 'self' === strtolower($className)) {
                $className = $declaringClass->name;
            } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
                $className = $parent->name;
            }

            $types[] = new Type(name: 'object', isNullable: $type->allowsNull(), className: $className);
        }

        return new Types($types);
    }
}
