<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Encoder;

use Symfony\Component\NewSerializer\Output\OutputInterface;

interface EncoderFactoryInterface
{
    public function create(OutputInterface $output): EncoderInterface;

    // TODO cache
    public function supports(string $format): bool;
}
