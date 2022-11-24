<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class ReflectionTypeExtractor implements TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): string
    {
        if (null === $type = $property->getType()) {
            throw new \InvalidArgumentException(sprintf('Type of "%s::$%s" has not been defined.', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        return $this->extractFromReflection($type, $property->getDeclaringClass());
    }

    public function extractFromReturnType(\ReflectionFunctionAbstract $function): string
    {
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $function->getReturnType()) {
            throw new \InvalidArgumentException(sprintf('Return type of "%s::%s()" has not been defined.', $declaringClass->getName(), $function->getName()));
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    private function extractFromReflection(\ReflectionNamedType|\ReflectionUnionType|\ReflectionIntersectionType $reflection, ?\ReflectionClass $declaringClass): string
    {
        $nullablePrefix = $reflection->allowsNull() ? '?' : '';

        if ($reflection instanceof \ReflectionIntersectionType) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if ($reflection instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn (\ReflectionNamedType $t): string => $this->extractFromReflection($t, $declaringClass), $reflection->getTypes()));
        }

        $phpTypeOrClass = $reflection->getName();

        if ('never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type.', $phpTypeOrClass));
        }

        if ('mixed' === $phpTypeOrClass || 'null' === $phpTypeOrClass) {
            return $phpTypeOrClass;
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
