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
final readonly class ReflectionPropertyResolver implements TypeResolverInterface
{
    public function __construct(
        private ReflectionTypeResolver $reflectionTypeResolver,
    ) {
    }

    public function resolve(mixed $subject): Type
    {
        if (!$subject instanceof \ReflectionProperty) {
            throw new UnsupportedException(sprintf('Expected subject to be a "ReflectionProperty", "%s" given.', get_debug_type($subject)));
        }

        try {
            return $this->reflectionTypeResolver->resolve($subject->getType(), $subject->getDeclaringClass());
        } catch (UnsupportedException $e) {
            $path = sprintf('%s::$%s', $subject->getDeclaringClass()->getName(), $subject->getName());

            throw new UnsupportedException(sprintf('Cannot resolve type for "%s".', $path), previous: $e);
        }
    }
}
