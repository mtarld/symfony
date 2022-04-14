<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Exporter\ChainExporter;
use App\Serializer\Output\OutputInterface;
use App\Serializer\Output\StringOutput;
use App\Serializer\Output\TemporaryStreamOutput;

final class Serializer
{
    /**
     * @var array<string, OutputInterface>
     */
    private array $outputs;

    public function __construct(
        /** @var iterable<EncoderInterface> */
        private iterable $encoders,
        /** @var iterable<Exporter> */
        private iterable $exporters,
    ) {
        $this->outputs = [
            'temporary_stream' => new TemporaryStreamOutput(),
            'string' => new StringOutput(),
        ];
    }

    public function serialize(mixed $value, string $format, string $output = 'temporary_stream'): OutputInterface
    {
        $encoder = $this->findEncoder($format)->withOutput($this->outputs[$output]);

        return (new ChainExporter($this->exporters, $encoder))->serialize($value);
    }

    private function findEncoder(string $format): EncoderInterface
    {
        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($format)) {
                return $encoder;
            }
        }

        throw new \RuntimeException('Missing encoder');
    }
}
