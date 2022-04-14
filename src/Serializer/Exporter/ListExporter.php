<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

final class ListExporter implements Exporter
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, ChainExporter $chainSerializer): OutputInterface
    {
        $generator = function () use ($value): \Generator {
            yield from $value;
        };

        $encoder->encodeList($generator, $chainSerializer);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'list' === $type;
    }
}
