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
        $formatters = [];

        foreach ($classPropertyValueFormatters as $className => $propertyValueFormatters) {
            foreach ($propertyValueFormatters as $propertyName => $valueFormatter) {
                $propertyIdentifier = sprintf('%s::$%s', $className, $propertyName);

                if (!is_callable($valueFormatter)) {
                    throw new \InvalidArgumentException(sprintf('Formatter "%s" of attribute "%s" is an invalid callable.', $propertyIdentifier, self::class));
                }

                $formatters[$propertyIdentifier] = \Closure::fromCallable($valueFormatter);
            }
        }

        $this->formatters = $formatters;
    }
}
