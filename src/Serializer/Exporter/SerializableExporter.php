<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;
use App\Serializer\SerializableInterface;

final class SerializableExporter implements Exporter
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
