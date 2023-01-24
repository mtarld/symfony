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
        $hookNames = [
            sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName()),
            'property',
        ];

        return $this->findHook($hookNames, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function extractFromType(Type|UnionType $type, array $context): ?callable
    {
        return $this->findHook($this->typeHookNames($type), $context);
    }

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

        return $this->findHook($hookNames, $context);
    }

    /**
     * @return list<string>
     */
    private function typeHookNames(Type|UnionType $type): array
    {
        $hookNames = ['type'];

        if ($type instanceof UnionType) {
            array_unshift($hookNames, 'union');
            array_unshift($hookNames, (string) $type);

            return $hookNames;
        }

        if ($type->isNull()) {
            array_unshift($hookNames, $type->name());

            return $hookNames;
        }

        if ($type->isObject()) {
            array_unshift($hookNames, $type->className(), 'object');
            if ($type->isNullable()) {
                array_unshift($hookNames, '?'.$type->className());
            }

            return $hookNames;
        }

        if ($type->isCollection()) {
            array_unshift($hookNames, 'collection');

            if ($type->isList()) {
                array_unshift($hookNames, 'list');
            }

            if ($type->isDict()) {
                array_unshift($hookNames, 'dict');
            }

            array_unshift($hookNames, $type->name());

            if ($type->isNullable()) {
                array_unshift($hookNames, '?'.$type->name());
            }

            return $hookNames;
        }

        array_unshift($hookNames, $type->name(), 'scalar');
        if ($type->isNullable()) {
            array_unshift($hookNames, '?'.$type->name());
        }

        return $hookNames;
    }

    /**
     * @param list<string>         $hookNames
     * @param array<string, mixed> $context
     */
    private function findHook(array $hookNames, array $context): ?callable
    {
        foreach ($hookNames as $hookName) {
            if (null !== ($hook = $context['hooks'][$hookName] ?? null)) {
                return $hook;
            }
        }

        return null;
    }
}
