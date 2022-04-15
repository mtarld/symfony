<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\EncoderFactory;
use App\Serializer\Serializer\ChainSerializer;
use App\Serializer\Output\OutputInterface;
use App\Serializer\Output\StringOutput;

final class Serializer implements SerializerInterface
{
    private OutputInterface $output;

    public function __construct(
        private iterable $serializers,
        private EncoderFactory $encoderFactory,
    ) {
        // TODO should be configurable (in FrameworkExtension - CompilerPass)
        // TODO what should be the default?
        $this->output = new StringOutput();
    }

    public function serialize(mixed $value, string $format): OutputInterface
    {
        $encoder = $this->encoderFactory->create($format, $this->output);

        return (new ChainSerializer($this->serializers, $encoder))->serialize($value);
    }

    public function withOutput(OutputInterface $output): static
    {
        $clone = clone $this;
        $clone->output = $output;

        return $clone;
    }
}
