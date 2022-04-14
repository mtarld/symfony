<?php

namespace App\Serializer\Encoder;

use App\Serializer\Exporter\ChainExporter;
use App\Serializer\Output\OutputInterface;

interface EncoderInterface
{
    // TODO cache
    public function supports(string $format): bool;

    public function encodeInt(int $value): void;

    public function encodeString(string $value): void;

    public function encodeDict(\Closure $generator, ChainExporter $serializer): void;

    public function encodeList(\Closure $generator, ChainExporter $serializer): void;

    public function withOutput(OutputInterface $output): static;

    public function getOutput(): OutputInterface|null;
}
