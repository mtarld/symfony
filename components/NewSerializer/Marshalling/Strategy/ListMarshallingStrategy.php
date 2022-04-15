<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Marshalling\Strategy;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;

final class ListMarshallingStrategy implements MarshallingStrategyInterface
{
    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void
    {
        $generator = function () use ($value): \Generator {
            yield from $value;
        };

        $encoder->encodeList($generator, $marshal);
    }

    public function canMarshal(mixed $value, string $type): bool
    {
        return 'list' === $type;
    }
}
