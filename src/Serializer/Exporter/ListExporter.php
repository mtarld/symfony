<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Output\Output;

final class ListExporter implements Exporter, EncoderAwareInterface
{
    use EncoderAwareTrait;

    public function export(mixed $value, string $type): Output
    {
        $generator = function () use ($value): \Generator {
            yield from $value;
        };

        $this->encoder->encodeList($generator);

        return $this->encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'list' === $type;
    }
}
