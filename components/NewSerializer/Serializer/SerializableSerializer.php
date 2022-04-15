<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Serializer;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;
use Symfony\Component\NewSerializer\SerializableInterface;

final class SerializableSerializer implements SerializerInterface
{
    /**
     * @param SerializableInterface $value
     */
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        return $value->serialize($encoder, $serialize);
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'object' === $type && $value instanceof SerializableInterface;
    }
}
