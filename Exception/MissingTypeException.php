<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class MissingTypeException extends InvalidArgumentException
{
    public function __construct(string $path, string $type)
    {
        parent::__construct(sprintf('Type of "%s" %s has not been defined.', $path, $type));
    }

    public static function createForProperty(\ReflectionProperty $property): self
    {
        return new self(sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName()), 'property');
    }

    public static function createForFunctionReturn(\ReflectionFunctionAbstract $function): self
    {
        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        $path = null !== $declaringClass
            ? sprintf('%s::%s()', $declaringClass->getName(), $function->getName())
            : sprintf('%s()', $function->getName());

        return new self($path, 'return');
    }

    public static function createForFunctionParameter(\ReflectionParameter $parameter): self
    {
        $function = $parameter->getDeclaringFunction();

        /** @var \ReflectionClass<object>|null $declaringClass */
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        $path = null !== $declaringClass
            ? sprintf('%s::%s($%s)', $declaringClass->getName(), $function->getName(), $parameter->getName())
            : sprintf('%s($%s)', $function->getName(), $parameter->getName());

        return new self($path, 'property');
    }
}
