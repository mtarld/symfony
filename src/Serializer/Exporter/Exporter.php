<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

// TODO this is a serializer
interface Exporter
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface;

    public function supports(mixed $value, string $type): bool;
}
