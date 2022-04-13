<?php

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Output\Output;
use App\Serializer\Serializer;

final class ScalarExporter implements Exporter
{
    public function export(mixed $value, string $type, Serializer $serializer, Encoder $encoder): Output
    {
        match ($type) {
            'int' => $encoder->encodeInt($value),
            'string' => $encoder->encodeString($value),
        };

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return \in_array($type, ['int', 'string']);
    }
}
