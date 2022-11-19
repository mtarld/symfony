<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class PropertyNameFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, array<string, callable>> $classPropertyNameFormatters
     */
    public function __construct(array $classPropertyNameFormatters)
    {
        $closures = [];

        foreach ($classPropertyNameFormatters as $className => $propertyNameFormatters) {
            foreach ($propertyNameFormatters as $propertyName => $nameFormatter) {
                $closures[sprintf('%s::$%s', $className, $propertyName)] = \Closure::fromCallable($nameFormatter);
            }
        }

        // TODO validate signature

        $this->formatters = $closures;
    }
}
