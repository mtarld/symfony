<?php

declare(strict_types=1);

namespace App\Serializer\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

final class ScalarSerializer implements SerializerInterface
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        match ($type) {
            'int' => $encoder->encodeInt($value),
            'string' => $encoder->encodeString($value),
        };

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return \in_array($type, ['int', 'string']);
    }
}
