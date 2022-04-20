<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Marshalling;

use Symfony\Component\Marshaller\Encoder\EncoderInterface;

interface MarshallableInterface
{
    public function marshal(EncoderInterface $encoder, \Closure $marshal): void;
}
