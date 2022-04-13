<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;

trait EncoderAwareTrait
{
    private Encoder|null $encoder = null;

    public function withEncoder(Encoder $encoder): static
    {
        $clone = clone $this;
        $clone->encoder = $encoder;

        return $clone;
    }
}
