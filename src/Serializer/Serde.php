<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Output\Output;
use App\Serializer\Output\StringOutput;
use App\Serializer\Output\TemporaryStreamOutput;

final class Serde
{
    /**
     * @var array<string, Output>
     */
    private array $outputs;

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

    public function serialize(mixed $value, string $format, string $output = 'temporary_stream'): Output
    {
        $output = $this->outputs[$output];
        $encoder = $this->findEncoder($format)->forOutput($output);
        $serializer = new Serializer($this->exporters, $encoder);

        return $serializer->serialize($value);
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
}
