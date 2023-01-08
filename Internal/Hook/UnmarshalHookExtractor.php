<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Hook;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

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
        $hookNames = [
            sprintf('%s[%s]', $className, $key),
            'property',
        ];

        if (null === $findHookResult = $this->findHook($hookNames, $context)) {
            return null;
        }

        [$hookName, $hook] = $findHookResult;

        $reflection = new \ReflectionFunction(\Closure::fromCallable($hook));

        if (5 !== \count($reflection->getParameters())) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have exactly 5 arguments.', $hookName));
        }

        $classParameterType = $reflection->getParameters()[0]->getType();
        if (!$classParameterType instanceof \ReflectionNamedType || \ReflectionClass::class !== $classParameterType->getName()) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have a "%s" for first argument.', $hookName, \ReflectionClass::class));
        }

        $objectParameterType = $reflection->getParameters()[1]->getType();
        if (!$objectParameterType instanceof \ReflectionNamedType || 'object' !== $objectParameterType->getName()) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have an "object" for second argument.', $hookName));
        }

        $nameParameterType = $reflection->getParameters()[2]->getType();
        if (!$nameParameterType instanceof \ReflectionNamedType || 'string' !== $nameParameterType->getName()) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have a "string" for third argument.', $hookName));
        }

        $valueParameterType = $reflection->getParameters()[3]->getType();
        if (!$valueParameterType instanceof \ReflectionNamedType || 'callable' !== $valueParameterType->getName()) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have a "callable" for fourth argument.', $hookName));
        }

        $contextParameterType = $reflection->getParameters()[4]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new InvalidArgumentException(sprintf('Hook "%s" must have an "array" for fifth argument.', $hookName));
        }

        return $hook;
    }

    /**
     * @param list<string>         $hookNames
     * @param array<string, mixed> $context
     *
     * @return array{0: string, 1: callable}|null
     */
    private function findHook(array $hookNames, array $context): ?array
    {
        foreach ($hookNames as $hookName) {
            if (null !== ($hook = $context['hooks'][$hookName] ?? null)) {
                return [$hookName, $hook];
            }
        }

        return null;
    }
}
