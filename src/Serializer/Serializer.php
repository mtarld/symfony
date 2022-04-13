<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Exporter\EncoderAwareInterface;
use App\Serializer\Exporter\Exporter;
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

        $type = $this->getType($value);

        $exporter = $this->findSerializer($value, $type, $this->exporters);
        if ($exporter instanceof EncoderAwareInterface) {
            $exporter = $exporter->withEncoder($this->encoder);
        }

        return $exporter->export($value, $type);
    }

    public function withEncoding(string $format, string $output = 'temporary_stream'): static
    {
        $clone = clone $this;
        $clone->encoder = $this->findEncoder($format)
            ->withOutput($this->outputs[$output])
            ->withSerializer($clone);

        return $clone;
    }

    private function findSerializer(mixed $value, string $type, iterable $serializers): Exporter
    {
        foreach ($serializers as $serializer) {
            if ($serializer->supports($value, $type)) {
                return $serializer;
            }
        }

        throw new \RuntimeException('Cannot find serializer');
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
