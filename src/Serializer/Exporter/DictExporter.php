<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

final class DictExporter implements Exporter
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        $generator = function () use ($value): \Generator {
            yield from $value;
        };

        $encoder->encodeDict($generator, $serialize);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'dict' === $type;
    }
}
