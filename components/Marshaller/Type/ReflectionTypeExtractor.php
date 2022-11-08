<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class ReflectionTypeExtractor
{
    /**
     * @return list<Type>|null
     */
    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): ?array
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection);
        }

        return $this->extractFromReturnType($reflection);
    }

    /**
     * @return list<Type>|null
     */
    private function extractFromProperty(\ReflectionProperty $property): ?array
    {
        try {
            if ($types = $this->extractFromType($property->getType(), $property->getDeclaringClass())) {
                return $types;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    /**
     * @return list<Type>|null
     */
    // TODO test
    private function extractFromReturnType(\ReflectionFunctionAbstract $function): ?array
    {
        try {
            if ($types = $this->extractFromType($function->getReturnType(), $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : null)) {
                return $types;
            }
        } catch (\ReflectionException) {
        }

        return null;
    }

    /**
     * @return list<Type>
     */
    private function extractFromType(\ReflectionType $type, \ReflectionClass $class): array
    {
        $types = [];
        $nullable = $type->allowsNull();

        foreach ($type instanceof \ReflectionUnionType ? $type->getTypes() : [$type] as $type) {
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

            if ('self' === strtolower($className)) {
                $className = $class->name;
            } elseif ('parent' === strtolower($className) && $parent = $class->getParentClass()) {
                $className = $parent->name;
            }

            $types[] = new Type(name: 'object', isNullable: $type->allowsNull(), className: $className);
        }

        return $types;
    }
}
