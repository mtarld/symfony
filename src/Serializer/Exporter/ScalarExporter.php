<?php

namespace App\Serializer\Exporter;

use App\Serializer\Output\Output;

final class ScalarExporter implements Exporter
{
    use EncoderAwareTrait;

    public function export(mixed $value, string $type): Output
    {
        match ($type) {
            'int' => $this->encoder->encodeInt($value),
            'string' => $this->encoder->encodeString($value),
        };

        return $this->encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return \in_array($type, ['int', 'string']);
    }
}
