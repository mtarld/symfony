<?php

declare(strict_types=1);

namespace App\Serializer\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

// TODO this is a serializer
interface SerializerInterface
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface;

    public function supports(mixed $value, string $type): bool;
}
