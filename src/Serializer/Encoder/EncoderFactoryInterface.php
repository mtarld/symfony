<?php

declare(strict_types=1);

namespace App\Serializer\Encoder;

use App\Serializer\Output\OutputInterface;

interface EncoderFactoryInterface
{
    public function create(OutputInterface $output): EncoderInterface;

    // TODO cache
    public function supports(string $format): bool;
}
