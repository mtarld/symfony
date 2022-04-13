<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Exporter\Exporter;
use App\Serializer\Output\Output;

final class Serializer
{
    public function __construct(
        /** @var iterable<Exporter> */
        private iterable $exporters,
        public readonly Encoder $encoder,
    ) {
    }

    public function serialize(mixed $value): Output
    {
        $type = $this->getType($value);
        $exporter = $this->findExporter($value, $type);

        return $exporter->export($value, $type, $this, $this->encoder);
    }

    private function getType(mixed $value): string
    {
        // TODO new TypeGuesserClass?

        $type = get_debug_type($value);
        if (is_object($value)) {
            $type = 'object';
        }

        if ('array' === $type) {
            $type = array_is_list($value) ? 'list' : 'dict';
        }

        return $type;
    }

    private function findExporter(mixed $value, string $type): Exporter
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($value, $type)) {
                return $exporter;
            }
        }

        throw new \RuntimeException('Cannot find exporter');
    }
}
