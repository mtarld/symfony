<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Hook;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class UnmarshalHookExtractor
{
    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    public function extractFromKey(string $className, string $key, array $context): ?callable
    {
        if (null === ($hook = $context['hooks'][$className][$key] ?? null)) {
            return null;
        }

        $reflection = new \ReflectionFunction(\Closure::fromCallable($hook));

        if (4 !== \count($reflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" of "%s" must have exactly 4 arguments.', $key, $className));
        }

        $classParameterType = $reflection->getParameters()[0]->getType();
        if (!$classParameterType instanceof \ReflectionNamedType || \ReflectionClass::class !== $classParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" of "%s" must have a "%s" for first argument.', $key, $className, \ReflectionClass::class));
        }

        $objectParameterType = $reflection->getParameters()[1]->getType();
        if (!$objectParameterType instanceof \ReflectionNamedType || 'object' !== $objectParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" of "%s" must have an "object" for second argument.', $key, $className));
        }

        $valueParameterType = $reflection->getParameters()[2]->getType();
        if (!$valueParameterType instanceof \ReflectionNamedType || 'callable' !== $valueParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" of "%s" must have a "callable" for third argument.', $key, $className));
        }

        $contextParameterType = $reflection->getParameters()[3]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Hook "%s" of "%s" must have an "array" for fourth argument.', $key, $className));
        }

        return $hook;
    }
}
