<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class PropertyValueFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, array<string, callable>> $classPropertyValueFormatters
     */
    public function __construct(array $classPropertyValueFormatters)
    {
        $closures = [];

        foreach ($classPropertyValueFormatters as $className => $propertyValueFormatters) {
            foreach ($propertyValueFormatters as $propertyName => $valueFormatter) {
                $closures[sprintf('%s::$%s', $className, $propertyName)] = \Closure::fromCallable($valueFormatter);
            }
        }

        // TODO validate signature

        $this->formatters = $closures;
    }
}
