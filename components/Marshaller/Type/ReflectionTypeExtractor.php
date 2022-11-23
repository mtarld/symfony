<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class ReflectionTypeExtractor
{
    public function extractFromProperty(\ReflectionProperty $property): string
    {
        return $this->extractFromReflection($property->getType(), $property->getDeclaringClass());
    }

    public function extractFromReturnType(\ReflectionFunction $function): string
    {
        return $this->extractFromReflection($function->getReturnType(), $function->getClosureScopeClass() ?? null);
    }

    private function extractFromReflection(\ReflectionType $reflection, ?\ReflectionClass $declaringClass): string
    {
        $nullablePrefix = $reflection->allowsNull() ? '?' : '';

        if ($reflection instanceof \ReflectionIntersectionType) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if ($reflection instanceof \ReflectionUnionType) {
            $nullable = false;
            $typeStrings = [];

            foreach ($reflection->getTypes() as $type) {
                $typeString = $this->extractFromReflection($type, $declaringClass);

                if (str_starts_with($typeString, '?')) {
                    $nullable = true;
                    $typeString = substr($typeString, 1);
                }

                $typeStrings[] = $typeString;
            }

            if ($nullable && !in_array('null', $typeStrings)) {
                $typeStrings[] = 'null';
            }

            return implode('|', $typeStrings);
        }

        $phpTypeOrClass = $reflection->getName();

        if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type.', $phpTypeOrClass));
        }

        if ('array' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type.', $phpTypeOrClass));
        }

        if ($reflection->isBuiltin()) {
            return $nullablePrefix.$phpTypeOrClass;
        }

        $className = $phpTypeOrClass;

        if ($declaringClass && 'self' === strtolower($className)) {
            $className = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
            $className = $parent->name;
        }

        return $nullablePrefix.$className;
    }
}
