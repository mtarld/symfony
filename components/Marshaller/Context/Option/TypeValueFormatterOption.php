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
        $formatters = [];

        foreach ($typeValueFormatters as $typeName => $valueFormatter) {
            if (!is_callable($valueFormatter)) {
                throw new \InvalidArgumentException(sprintf('Formatter "%s" of attribute "%s" is an invalid callable.', $typeName, self::class));
            }

            $formatters[$typeName] = \Closure::fromCallable($valueFormatter);
        }

        $this->formatters = $formatters;
    }
}
