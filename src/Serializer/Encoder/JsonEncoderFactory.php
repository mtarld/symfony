<?php

declare(strict_types=1);

namespace App\Serializer\Encoder;

use App\Serializer\Output\OutputInterface;

final class JsonEncoderFactory implements EncoderFactoryInterface
{
    public function create(OutputInterface $output): JsonEncoder
    {
        return new JsonEncoder($output);
    }

    public function supports(string $format): bool
    {
        return 'json' === $format;
    }
}
