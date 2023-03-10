<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Type;

use Symfony\Component\Marshaller\Exception\MissingTypeException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class ReflectionTypeExtractor implements TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): string
    {
        if (null === $type = $property->getType()) {
            throw MissingTypeException::forProperty($property);
        }

        return $this->extractFromReflection($type, $property->getDeclaringClass());
    }

    public function extractFromFunctionReturn(\ReflectionFunctionAbstract $function): string
    {
        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $function->getReturnType()) {
            throw MissingTypeException::forFunctionReturn($function);
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    public function extractFromFunctionParameter(\ReflectionParameter $parameter): string
    {
        $function = $parameter->getDeclaringFunction();

        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $parameter->getType()) {
            throw MissingTypeException::forFunctionParameter($parameter);
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    /**
     * @param \ReflectionClass<object>|null $declaringClass
     */
    private function extractFromReflection(\ReflectionType $reflection, ?\ReflectionClass $declaringClass): string
    {
        if (!($reflection instanceof \ReflectionUnionType || $reflection instanceof \ReflectionNamedType)) {
            throw new UnsupportedTypeException((string) $reflection);
        }

        if ($reflection instanceof \ReflectionUnionType) {
            return implode('|', array_map(fn (\ReflectionNamedType $t): string => $this->extractFromReflection($t, $declaringClass), $reflection->getTypes()));
        }

        $nullablePrefix = $reflection->allowsNull() ? '?' : '';
        $phpTypeOrClass = $reflection->getName();

        if ('never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new UnsupportedTypeException($phpTypeOrClass);
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
