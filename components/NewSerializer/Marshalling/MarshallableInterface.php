<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Marshalling;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;

interface MarshallableInterface
{
    public function marshal(EncoderInterface $encoder, \Closure $serialize): void;
}
