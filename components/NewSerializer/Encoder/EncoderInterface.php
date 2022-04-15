<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Encoder;

interface EncoderInterface
{
    public function encodeInt(int $value): void;

    public function encodeString(string $value): void;

    public function encodeDict(\Closure $generator, \Closure $serialize): void;

    public function encodeList(\Closure $generator, \Closure $serialize): void;
}
