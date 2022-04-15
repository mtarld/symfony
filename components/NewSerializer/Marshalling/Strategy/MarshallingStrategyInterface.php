<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Marshalling\Strategy;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;

interface MarshallingStrategyInterface
{
    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void;

    public function canMarshal(mixed $value, string $type): bool;
}
