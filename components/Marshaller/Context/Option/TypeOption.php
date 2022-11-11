<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class TypeOption
{
    public function __construct(
        public readonly string $type,
    ) {
        if ('' === $type) {
            throw new \InvalidArgumentException(sprintf('Type of attribute "%s" must be an non empty string.', self::class));
        }
    }
}
