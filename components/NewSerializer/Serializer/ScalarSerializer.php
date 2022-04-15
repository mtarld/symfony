<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Serializer;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;

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
