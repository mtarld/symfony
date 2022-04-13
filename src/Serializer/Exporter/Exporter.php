<?php

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Output\Output;
use App\Serializer\Serializer;

// TODO this is a serializer
interface Exporter
{
    public function export(mixed $value, string $type, Serializer $serializer, Encoder $encoder): Output;

    public function supports(mixed $value, string $type): bool;
}

