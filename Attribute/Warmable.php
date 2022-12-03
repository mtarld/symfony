<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Warmable
{
    public function __construct(
        public readonly ?bool $nullable = null,
    ) {
    }
}
