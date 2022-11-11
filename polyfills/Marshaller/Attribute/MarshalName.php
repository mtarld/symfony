<?php

declare(strict_types=1);

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final class MarshalName
{
    public function __construct(
        public readonly string $name,
    ) {
        if ('' === $name) {
            throw new \InvalidArgumentException(sprintf('Parameter "name" of attribute "%s" must be a non-empty string.', self::class));
        }
    }
}

