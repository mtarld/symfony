<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class TypeOption
{
    public function __construct(
        public readonly string $type,
    ) {
    }
}
