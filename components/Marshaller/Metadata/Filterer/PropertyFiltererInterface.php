<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Filterer;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\PropertyMetadata\PropertyMetadata;

interface PropertyFiltererInterface
{
    /**
     * @param list<PropertyMetadata> $properties
     *
     * @return list<PropertyMetadata>
     */
    public function filter(array $properties, Context $context): array;
}
