<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Decoder\DecoderFactoryInterface;
use Symfony\Component\Marshaller\Input\InputInterface;
use Symfony\Component\Marshaller\Unmarshalling\Unmarshaller as UnmarshallingUnmarshaller;

final class Unmarshaller implements UnmarshallerInterface
{
    public function __construct(
        private iterable $unmarshallingStrategies,
        private DecoderFactoryInterface $decoderFactory,
    ) {
    }

    public function unmarshal(InputInterface $input, string $type): mixed
    {
        $decoder = $this->decoderFactory->create($input);
        foreach ($decoder as $key => $value) {
            dump($value);
        }

        return 0;

        // $marshaller = new UnmarshallingUnmarshaller($this->unmarshallingStrategies, $decoder);
        //
        // $marshaller->unmarshal($type);
    }
}
