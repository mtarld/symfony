<?php

namespace App\Serializer\Encoder;

use App\Serializer\Output\Output;
use App\Serializer\Serializer;

interface Encoder
{
    // TODO cache
    public function supports(string $format): bool;

    public function encodeInt(int $value): void;

    public function encodeString(string $value): void;

    public function encodeDict(\Closure $generator, Serializer $serializer): void;

    public function encodeList(\Closure $generator, Serializer $serializer): void;

    public function forOutput(Output $output): static;

    public function getOutput(): Output|null;
}
