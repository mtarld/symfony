<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class TypeFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, callable> $typeFormatters
     */
    public function __construct(array $typeFormatters)
    {
        $formatters = [];

        foreach ($typeFormatters as $typeName => $formatter) {
            if (!is_callable($formatter)) {
                throw new \InvalidArgumentException(sprintf('Formatter "%s" of attribute "%s" is an invalid callable.', $typeName, self::class));
            }

            $formatters[$typeName] = \Closure::fromCallable($formatter);
        }

        $this->formatters = $formatters;
    }
}
