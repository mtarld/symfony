<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Marshalling;

use Symfony\Component\Marshaller\Encoder\EncoderInterface;
use Symfony\Component\Marshaller\Marshalling\Strategy\MarshallingStrategyInterface;

/**
 * @internal
 */
final class Marshaller
{
    public function __construct(
        /** @var iterable<MarshallingStrategyInterface> */
        private iterable $marshallingStrategies,
        private EncoderInterface $encoder,
    ) {
    }

    public function marshal(mixed $data): void
    {
        $type = $this->getType($data);
        $marshallingStrategy = $this->findMarshallingStrategy($data, $type);

        $marshallingStrategy->marshal($data, $type, $this->encoder, $this->marshal(...));
    }

    private function findMarshallingStrategy(mixed $data, string $type): MarshallingStrategyInterface
    {
        foreach ($this->marshallingStrategies as $strategy) {
            if ($strategy->canMarshal($data, $type)) {
                return $strategy;
            }
        }

        throw new \RuntimeException('Cannot find marshalling strategy');
    }

    private function getType(mixed $data): string
    {
        // TODO new TypeGuesserClass?

        $type = get_debug_type($data);
        if (is_object($data)) {
            $type = 'object';
        }

        if ('array' === $type) {
            $type = array_is_list($data) ? 'list' : 'dict';
        }

        return $type;
    }
}
