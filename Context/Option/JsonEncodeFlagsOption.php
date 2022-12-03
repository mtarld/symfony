<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class JsonEncodeFlagsOption
{
    public function __construct(
        public readonly int $flags,
    ) {
    }
}
