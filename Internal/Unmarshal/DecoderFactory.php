<?php

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDecoder;

abstract class DecoderFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): DecoderInterface
    {
        return match ($format) {
            'json' => new JsonDecoder(),
            default => throw new UnsupportedFormatException($format),
        };
    }
}

