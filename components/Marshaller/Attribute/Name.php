<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Name
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
