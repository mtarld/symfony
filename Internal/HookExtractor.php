<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class HookExtractor
{
    /**
     * @param array<string, mixed> $context
     */
    public function forProperty(\ReflectionProperty $property, array $context): ?callable
    {
        if (isset($context['hooks'][$property->getDeclaringClass()->getName().'::$'.$property->getName()])) {
            return $context['hooks'][$property->getDeclaringClass()->getName().'::$'.$property->getName()];
        }

        if (isset($context['hooks']['property'])) {
            return $context['hooks']['property'];
        }

        return null;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    public function forObject(string $className, array $context): ?callable
    {
        if (isset($context['hooks'][$className])) {
            return $context['hooks'][$className];
        }

        if (isset($context['hooks']['object'])) {
            return $context['hooks']['object'];
        }

        return null;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    public function forKey(string $className, string $key, array $context): ?callable
    {
        if (isset($context['hooks'][$className.'['.$key.']'])) {
            return $context['hooks'][$className.'['.$key.']'];
        }

        if (isset($context['hooks']['property'])) {
            return $context['hooks']['property'];
        }

        return null;
    }
}
