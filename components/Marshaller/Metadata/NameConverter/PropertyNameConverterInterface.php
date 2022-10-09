<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\NameConverter;

use Symfony\Component\Marshaller\Metadata\Attribute\Attributes;

interface PropertyNameConverterInterface
{
    public function convert(string $initialName, Attributes $attributes): string;
}
