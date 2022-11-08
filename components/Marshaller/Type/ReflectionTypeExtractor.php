<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class ReflectionTypeExtractor
{
    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): ?Type
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection, $declaringClass);
        }

        return $this->extractFromReturnType($reflection, $declaringClass);
    }

    private function extractFromProperty(\ReflectionProperty $property, \ReflectionClass $declaringClass): ?Type
    {
        try {
            if ($type = $this->extractFromType($property->getType(), $declaringClass)) {
                return $type;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    // TODO test
    private function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Type
    {
        try {
            if ($type = $this->extractFromType($function->getReturnType(), $declaringClass)) {
                return $type;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    private function extractFromType(\ReflectionType $type, \ReflectionClass $declaringClass): ?Type
    {
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        $phpTypeOrClass = $type->getName();

        if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            return null;
        }

        if ('array' === $phpTypeOrClass) {
            return new Type(name: 'array', isNullable: $type->allowsNull(), isCollection: true);
        }
        if ($type->isBuiltin()) {
            return new Type(name: $phpTypeOrClass, isNullable: $type->allowsNull());
        }

        $className = $phpTypeOrClass;

        if ($declaringClass && 'self' === strtolower($className)) {
            $className = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
            $className = $parent->name;
        }

        return new Type(name: 'object', isNullable: $type->allowsNull(), className: $className);
    }
}
