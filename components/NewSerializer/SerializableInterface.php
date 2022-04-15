<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;

interface SerializableInterface
{
    public function serialize(EncoderInterface $encoder, \Closure $serialize): OutputInterface;
}
