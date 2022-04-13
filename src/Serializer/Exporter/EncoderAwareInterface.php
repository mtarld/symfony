<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;

interface EncoderAwareInterface
{
    public function withEncoder(Encoder $encoder): static;
}
