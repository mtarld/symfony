<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Resolver;

use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final readonly class ReflectionReturnResolver implements TypeResolverInterface
{
    public function __construct(
        private ReflectionTypeResolver $reflectionTypeResolver,
    ) {
    }

    public function resolve(mixed $subject): Type
    {
        if (!$subject instanceof \ReflectionFunctionAbstract) {
            throw new UnsupportedException(sprintf('Expected subject to be a "ReflectionFunctionAbstract", "%s" given.', get_debug_type($subject)));
        }

        $declaringClass = $subject instanceof \ReflectionMethod ? $subject->getDeclaringClass() : $subject->getClosureScopeClass();

        try {
            return $this->reflectionTypeResolver->resolve($subject->getReturnType(), $declaringClass);
        } catch (UnsupportedException $e) {
            $path = null !== $declaringClass
                ? sprintf('%s::%s()', $declaringClass->getName(), $subject->getName())
                : sprintf('%s()', $subject->getName());

            throw new UnsupportedException(sprintf('Cannot resolve type for "%s".', $path), previous: $e);
        }
    }
}
