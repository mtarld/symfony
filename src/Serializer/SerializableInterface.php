<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

interface SerializableInterface
{
    public function serialize(EncoderInterface $encoder, \Closure $serialize): OutputInterface;
}
