<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Encoder;

use Symfony\Component\NewSerializer\Output\OutputInterface;

final class JsonEncoderFactory implements EncoderFactoryInterface
{
    public function create(OutputInterface $output): JsonEncoder
    {
        return new JsonEncoder($output);
    }
}
