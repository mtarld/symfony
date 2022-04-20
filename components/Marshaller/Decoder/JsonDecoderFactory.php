<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Decoder;

use Symfony\Component\Marshaller\Input\InputInterface;

final class JsonDecoderFactory implements DecoderFactoryInterface
{
    public function create(InputInterface $input): JsonDecoder
    {
        return new JsonDecoder($input);
    }
}
