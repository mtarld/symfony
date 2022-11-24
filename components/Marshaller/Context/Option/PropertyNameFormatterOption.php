<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class PropertyNameFormatterOption
{
    /**
     * @var array<string, callable>
     */
    public readonly array $formatters;

    /**
     * @param array<string, array<string, callable>> $classPropertyNameFormatters
     */
    public function __construct(array $classPropertyNameFormatters)
    {
        $formatters = [];

        foreach ($classPropertyNameFormatters as $className => $propertyNameFormatters) {
            foreach ($propertyNameFormatters as $propertyName => $nameFormatter) {
                $propertyIdentifier = sprintf('%s::$%s', $className, $propertyName);

                if (!is_callable($nameFormatter)) {
                    throw new \InvalidArgumentException(sprintf('Formatter "%s" of attribute "%s" is an invalid callable.', $propertyIdentifier, self::class));
                }

                $formatters[$propertyIdentifier] = \Closure::fromCallable($nameFormatter);
            }
        }

        $this->formatters = $formatters;
    }
}
