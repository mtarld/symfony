<?php

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Output\Output;
use App\Serializer\Serializer;

final class ListExporter implements Exporter
{
    public function export(mixed $value, string $type, Serializer $serializer, Encoder $encoder): Output
    {
        $generator = function () use ($value): \Generator {
            foreach ($value as $k => $v) {
                yield $k => $v;
            }
        };

        $encoder->encodeList($generator, $serializer);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'list' === $type;
    }
}
