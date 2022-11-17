<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template;

/**
 * @internal
 */
trait VariableNameScoperTrait
{
    protected function scopeVariableName(string $prefix, array &$context): string
    {
        if (!isset($context['variable_counters'][$prefix])) {
            $context['variable_counters'][$prefix] = 0;
        }

        $name = sprintf('$%s_%d', $prefix, $context['variable_counters'][$prefix]);

        ++$context['variable_counters'][$prefix];

        return $name;
    }
}
