<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Unmarshalling\Strategy;

use Symfony\Component\Marshaller\Decoder\DecoderInterface;

interface UnmarshallingStrategyInterface
{
    public function unmarshal(mixed $value, string $type, DecoderInterface $decoder, \Closure $unmarshal): void;

    public function canUnmarshal(mixed $value, string $type): bool;
}
