<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Hook;

use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

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
    public function extractFromProperty(\ReflectionProperty $property, array $context): ?callable
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
     * @param array<string, mixed> $context
     */
    public function extractFromType(Type|UnionType $type, array $context): ?callable
    {
        // TODO
        throw new \BadMethodCallException(__METHOD__);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function extractFromObject(Type $type, array $context): ?callable
    {
        if (isset($context['hooks']['?'.($className = $type->className())]) && $type->isNullable()) {
            return $context['hooks']['?'.$className];
        }

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
    public function extractFromKey(string $className, string $key, array $context): ?callable
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
