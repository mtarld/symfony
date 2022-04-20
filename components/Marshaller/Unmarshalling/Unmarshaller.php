<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Unmarshalling;

use Symfony\Component\Marshaller\Decoder\DecoderInterface;
use Symfony\Component\Marshaller\Unmarshalling\Strategy\UnmarshallingStrategyInterface;

/**
 * @internal
 */
final class Unmarshaller
{
    public function __construct(
        /** @var iterable<UnmarshallingStrategyInterface> */
        private iterable $unmarshallingStrategies,
        private DecoderInterface $decoder,
    ) {
    }

    public function unmarshal(mixed $data, string $type): mixed
    {
        $unmarshallingStrategy = $this->findUnmarshallingStrategy($data, $type);

        $unmarshallingStrategy->unmarshal($data, $type, $this->decoder, $this->unmarshal(...));
    }

    private function findUnmarshallingStrategy(mixed $data, string $type): UnmarshallingStrategyInterface
    {
        foreach ($this->unmarshallingStrategies as $strategy) {
            if ($strategy->canUnmarshal($data, $type)) {
                return $strategy;
            }
        }

        throw new \RuntimeException('Cannot find unmarshalling strategy');
    }
}

