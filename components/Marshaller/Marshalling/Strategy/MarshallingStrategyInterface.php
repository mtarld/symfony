<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Marshalling\Strategy;

use Symfony\Component\Marshaller\Encoder\EncoderInterface;

interface MarshallingStrategyInterface
{
    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void;

    public function canMarshal(mixed $value, string $type): bool;
}
