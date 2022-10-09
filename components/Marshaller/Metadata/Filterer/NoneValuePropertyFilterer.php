<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Filterer;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\PropertyMetadata;

final class NoneValuePropertyFilterer implements PropertyFiltererInterface
{
    public function __construct(
        private readonly PropertyFiltererInterface $inner,
    ) {
    }

    public function filter(array $properties, Context $context): array
    {
        return $this->inner->filter($this->filterNoneValues($properties), $context);
    }

    /**
     * @param list<PropertyMetadata> $properties
     *
     * @return list<PropertyMetadata>
     */
    private function filterNoneValues(array $properties): array
    {
        foreach ($properties as $k => $property) {
            if (!$property->value->isNone()) {
                continue;
            }

            unset($properties[$k]);
        }

        return array_values($properties);
    }
}
