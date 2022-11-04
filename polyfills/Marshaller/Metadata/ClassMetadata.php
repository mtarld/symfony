<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

final class ClassMetadata
{
    /**
     * @param list<PropertyMetadata> $properties
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties,
    ) {
    }
}

