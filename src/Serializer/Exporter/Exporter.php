<?php

namespace App\Serializer\Exporter;

use App\Serializer\Output\Output;

// TODO this is a serializer
interface Exporter
{
    public function export(mixed $value, string $type): Output;

    public function supports(mixed $value, string $type): bool;
}

