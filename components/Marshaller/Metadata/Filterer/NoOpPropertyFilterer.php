<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Filterer;

use Symfony\Component\Marshaller\Context\Context;

final class NoOpPropertyFilterer implements PropertyFiltererInterface
{
    public function filter(array $properties, Context $context): array
    {
        return $properties;
    }
}
