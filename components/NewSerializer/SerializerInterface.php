<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer;

use Symfony\Component\NewSerializer\Output\OutputInterface;

interface SerializerInterface
{
    public function serialize(mixed $value, string $format): OutputInterface;

    public function withOutput(OutputInterface $output): static;
}
