<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Marshalling\Strategy;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Marshalling\MarshallableInterface;

final class MarshallableMarshallingStrategy implements MarshallingStrategyInterface
{
    /**
     * @param MarshallableInterface $value
     */
    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void
    {
        $value->marshal($encoder, $marshal);
    }

    public function canMarshal(mixed $value, string $type): bool
    {
        return 'object' === $type && $value instanceof MarshallableInterface;
    }
}
