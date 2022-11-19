<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class NameFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, callable> $formatters
     */
    public function __construct(array $formatters)
    {
        $closures = [];

        foreach ($formatters as $propertyName => $formatter) {
            $closures[$propertyName] = \Closure::fromCallable($formatter);
        }

        // TODO validate signature

        $this->formatters = $closures;
    }
}
