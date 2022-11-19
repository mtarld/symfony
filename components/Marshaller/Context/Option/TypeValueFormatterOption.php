<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class TypeValueFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, callable> $typeValueFormatters
     */
    public function __construct(array $typeValueFormatters)
    {
        $closures = [];

        foreach ($typeValueFormatters as $typeName => $valueFormatter) {
            $closures[$typeName] = \Closure::fromCallable($valueFormatter);
        }

        // TODO validate signature

        $this->formatters = $closures;
    }
}
