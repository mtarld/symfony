<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
trait VariableNameScoperTrait
{
    /**
     * @param array<string, mixed> $context
     */
    protected function scopeVariableName(string $prefix, array &$context): string
    {
        if (!isset($context['variable_counters'][$prefix])) {
            $context['variable_counters'][$prefix] = 0;
        }

        $name = sprintf('%s_%d', $prefix, $context['variable_counters'][$prefix]);

        ++$context['variable_counters'][$prefix];

        return $name;
    }
}
