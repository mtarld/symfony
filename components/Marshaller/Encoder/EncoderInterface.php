<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Encoder;

interface EncoderInterface
{
    public function encodeInt(int $int): void;

    public function encodeString(string $string): void;

    public function encodeDict(\Generator $dict, \Closure $marshal): void;

    public function encodeList(\Generator $list, \Closure $marshal): void;
}
