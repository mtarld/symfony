<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
trait VariableNameScoperTrait
{
    /**
     * @param array<string, mixed> $context
     */
    protected function scopeVariableName(string $variableName, array &$runtime): string
    {
        if (!isset($runtime['variable_counters'][$variableName])) {
            $runtime['variable_counters'][$variableName] = 0;
        }

        $name = sprintf('%s_%d', $variableName, $runtime['variable_counters'][$variableName]);

        ++$runtime['variable_counters'][$variableName];

        return $name;
    }
}
