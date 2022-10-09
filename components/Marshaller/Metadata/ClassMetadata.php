<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

final class ClassMetadata
{
    /**
     * @param array<PropertyMetadata> $properties
     */
    public function __construct(
        public readonly string $class,
        public readonly array $properties,
    ) {
    }
}
