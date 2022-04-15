<?php

declare(strict_types=1);

namespace App\Serializer\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

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
