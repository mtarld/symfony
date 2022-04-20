<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Decoder;

interface DecoderInterface extends \IteratorAggregate
{
    public function decodeInt(mixed $data): int;

    public function decodeString(mixed $data): string;

    public function decodeDict(mixed $data, \Closure $unmarshal): iterable;

    public function decodeList(\Generator $data, \Closure $unmarshal): iterable;
}
