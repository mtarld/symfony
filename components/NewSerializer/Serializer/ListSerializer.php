<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Serializer;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;

final class ListSerializer implements SerializerInterface
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        $generator = function () use ($value): \Generator {
            yield from $value;
        };

        $encoder->encodeList($generator, $serialize);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'list' === $type;
    }
}
