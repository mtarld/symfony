<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Encoder;

use Symfony\Component\Marshaller\Output\OutputInterface;

final class JsonEncoderFactory implements EncoderFactoryInterface
{
    public function create(OutputInterface $output): JsonEncoder
    {
        return new JsonEncoder($output);
    }
}
