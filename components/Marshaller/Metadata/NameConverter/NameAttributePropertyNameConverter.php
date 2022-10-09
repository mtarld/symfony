<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\NameConverter;

use Symfony\Component\Marshaller\Metadata\Attribute\Attributes;
use Symfony\Component\Marshaller\Metadata\Attribute\NameAttribute;

final class NameAttributePropertyNameConverter implements PropertyNameConverterInterface
{
    public function __construct(
        private PropertyNameConverterInterface $fallbackNameConverter,
    ) {
    }

    public function convert(string $initialName, Attributes $attributes): string
    {
        if (!$attributes->has(NameAttribute::class)) {
            return $this->fallbackNameConverter->convert($initialName, $attributes);
        }

        return $attributes->get(NameAttribute::class)->name;
    }
}
