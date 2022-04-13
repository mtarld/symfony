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

    public function encodeDict(\Closure $generator): void;

    public function encodeList(\Closure $generator): void;

    public function withOutput(Output $output): static;

    public function getOutput(): Output|null;

    public function withSerializer(Serializer $serializer): static;
}
