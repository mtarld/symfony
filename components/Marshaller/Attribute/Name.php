<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final class Name
{
    public function __construct(
        public readonly string $name,
    ) {
        if ('' === $name) {
            throw new \InvalidArgumentException(sprintf('Parameter of attribute "%s" must be a non-empty string.', self::class));
        }
    }
}
