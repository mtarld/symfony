<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Encoder;

use Symfony\Component\Marshaller\Output\OutputInterface;

interface EncoderFactoryInterface
{
    public function create(OutputInterface $output): EncoderInterface;
}
