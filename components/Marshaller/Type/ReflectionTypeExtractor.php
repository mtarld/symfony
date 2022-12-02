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
        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $declaringClass) {
            throw new \InvalidArgumentException(sprintf('Cannot find class related to "%s()".', $function->getName()));
        }

        if (null === $type = $function->getReturnType()) {
            throw new \InvalidArgumentException(sprintf('Return type of "%s::%s()" has not been defined.', $declaringClass->getName(), $function->getName()));
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    /**
     * @param \ReflectionClass<object>|null $declaringClass
     */
    private function extractFromReflection(\ReflectionType $reflection, ?\ReflectionClass $declaringClass): string
    {
        if ($reflection instanceof \ReflectionIntersectionType) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if ($reflection instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn (\ReflectionNamedType $t): string => $this->extractFromReflection($t, $declaringClass), $reflection->getTypes()));
        }

        if (!$reflection instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException(sprintf('Unexpected "%s" type reflection.', $reflection::class));
        }

        $nullablePrefix = $reflection->allowsNull() ? '?' : '';
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

    public function extractTemplateFromClass(\ReflectionClass $class): array
    {
        return [];
    }
}
