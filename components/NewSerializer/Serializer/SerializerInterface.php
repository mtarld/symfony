<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Serializer;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;

interface SerializerInterface
{
    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface;

    public function supports(mixed $value, string $type): bool;
}
