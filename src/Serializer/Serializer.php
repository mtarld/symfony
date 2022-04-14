<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Exporter\ChainExporter;
use App\Serializer\Output\Output;
use App\Serializer\Output\StringOutput;
use App\Serializer\Output\TemporaryStreamOutput;

final class Serializer
{
    /**
     * @var array<string, Output>
     */
    private array $outputs;

    private Encoder|null $encoder = null;
    private ChainExporter|null $chainSerializer = null;

    public function __construct(
        /** @var iterable<Encoder> */
        private iterable $encoders,
        /** @var iterable<Exporter> */
        private iterable $exporters,
    ) {
        $this->outputs = [
            'temporary_stream' => new TemporaryStreamOutput(),
            'string' => new StringOutput(),
        ];
    }

    public function serialize(mixed $value): Output
    {
        if (!$this->encoder) {
            throw new \RuntimeException('Missing encoder');
        }

        if (!$this->chainSerializer) {
            throw new \RuntimeException('Missing chainSerializer');
        }

        return $this->chainSerializer->export($value, $this->getType($value));
    }

    public function withEncoding(string $format, string $output = 'temporary_stream'): static
    {
        $clone = clone $this;

        $encoder = $this->findEncoder($format)
            ->withOutput($this->outputs[$output])
            ->withSerializer($clone);

        $clone->encoder = $encoder;
        $clone->chainSerializer = new ChainExporter($clone->exporters, $clone->encoder);

        return $clone;
    }

    private function findEncoder(string $format): Encoder
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($format)) {
                return $encoder;
            }
        }

        throw new \RuntimeException('Missing encoder');
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
}
