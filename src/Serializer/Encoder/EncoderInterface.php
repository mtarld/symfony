<?php

namespace App\Serializer\Encoder;

use App\Serializer\Output\OutputInterface;

interface EncoderInterface
{
    public function encodeInt(int $value): void;

    public function encodeString(string $value): void;

    public function encodeDict(\Closure $generator, \Closure $serialize): void;

    public function encodeList(\Closure $generator, \Closure $serialize): void;

    public function getOutput(): OutputInterface|null;
}
