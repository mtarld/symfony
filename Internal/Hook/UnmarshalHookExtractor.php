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
        $hookNames = [
            sprintf('%s[%s]', $className, $key),
            'property',
        ];

        return $this->findHook($hookNames, $context);
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
