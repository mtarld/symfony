<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Type;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class ReflectionTypeExtractor implements TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): Type
    {
        if (null === $type = $property->getType()) {
            throw new InvalidArgumentException(sprintf('Type of "%s::$%s" property has not been defined.', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        return $this->extractFromReflection($type, $property->getDeclaringClass());
    }

    public function extractFromFunctionReturn(\ReflectionFunctionAbstract $function): Type
    {
        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $function->getReturnType()) {
            $path = null !== $declaringClass
                ? sprintf('%s::%s()', $declaringClass->getName(), $function->getName())
                : sprintf('%s()', $function->getName());

            throw new InvalidArgumentException(sprintf('Type of "%s" return value has not been defined.', $path));
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    public function extractFromFunctionParameter(\ReflectionParameter $parameter): Type
    {
        $function = $parameter->getDeclaringFunction();

        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $type = $parameter->getType()) {
            $path = null !== $declaringClass
                ? sprintf('%s::%s($%s)', $declaringClass->getName(), $function->getName(), $parameter->getName())
                : sprintf('%s($%s)', $function->getName(), $parameter->getName());

            throw new InvalidArgumentException(sprintf('Type of "%s" parameter has not been defined.', $path));
        }

        return $this->extractFromReflection($type, $declaringClass);
    }

    /**
     * @param \ReflectionClass<object>|null $declaringClass
     */
    private function extractFromReflection(\ReflectionType $reflection, ?\ReflectionClass $declaringClass): Type
    {
        if (!($reflection instanceof \ReflectionUnionType || $reflection instanceof \ReflectionNamedType)) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', (string) $reflection));
        }

        if ($reflection instanceof \ReflectionUnionType) {
            /** @var list<Type> $unionTypes */
            $unionTypes = array_map(fn (\ReflectionNamedType $t): Type => $this->extractFromReflection($t, $declaringClass), $reflection->getTypes());

            return new Type((string) $reflection, unionTypes: $unionTypes);
        }

        $nullablePrefix = $reflection->allowsNull() ? '?' : '';
        $phpTypeOrClass = $reflection->getName();

        if ('never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', $phpTypeOrClass));
        }

        if ('mixed' === $phpTypeOrClass || 'null' === $phpTypeOrClass) {
            return new Type($phpTypeOrClass);
        }

        if ($reflection->isBuiltin()) {
            return new Type($phpTypeOrClass, isNullable: $reflection->allowsNull());
        }

        /** @var class-string $className */
        $className = $phpTypeOrClass;

        if ($declaringClass && 'self' === strtolower($className)) {
            $className = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
            $className = $parent->name;
        }

        return new Type('object', isNullable: $reflection->allowsNull(), className: $className);
    }

    public function extractTemplateFromClass(\ReflectionClass $class): array
    {
        return [];
    }
}
