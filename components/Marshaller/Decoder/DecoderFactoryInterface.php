<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Decoder;

use Symfony\Component\Marshaller\Input\InputInterface;

interface DecoderFactoryInterface
{
    public function create(InputInterface $output): DecoderInterface;
}
