<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Output\OutputInterface;

interface SerializerInterface
{
    public function serialize(mixed $value, string $format): OutputInterface;

    public function withOutput(OutputInterface $output): static;
}
