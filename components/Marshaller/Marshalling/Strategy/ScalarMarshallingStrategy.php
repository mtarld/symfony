<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Marshalling\Strategy;

use Symfony\Component\Marshaller\Encoder\EncoderInterface;

final class ScalarMarshallingStrategy implements MarshallingStrategyInterface
{
    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void
    {
        match ($type) {
            'int' => $encoder->encodeInt($value),
            'string' => $encoder->encodeString($value),
        };
    }

    public function canMarshal(mixed $value, string $type): bool
    {
        return \in_array($type, ['int', 'string']);
    }
}
