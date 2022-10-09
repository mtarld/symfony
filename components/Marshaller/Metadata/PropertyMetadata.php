<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

use Symfony\Component\Marshaller\Metadata\Attribute\Attributes;

final class PropertyMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $convertedName,
        public readonly ValueMetadata $value,
        public readonly bool $isPublic,
        public readonly Attributes $attributes,
    ) {
    }
}
