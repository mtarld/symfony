<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Output\Output;

final class ChainExporter 
{
    public function __construct(
        private iterable $serializers,
        private Encoder $encoder,
    ) {
    }

    public function export(mixed $value, string $type): Output
    {
        return $this->findSerializer($value, $type)->export($value, $type);
    }

    private function findSerializer(mixed $value, string $type): Exporter
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($value, $type)) {
                return $serializer->withEncoder($this->encoder);
            }
        }

        throw new \RuntimeException('Cannot find serializer');
    }
}

