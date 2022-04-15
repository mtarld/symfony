<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Marshalling;

use Symfony\Component\NewSerializer\Encoder\EncoderFactoryInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;

/**
 * @internal
 */
final class MarshallerFactory
{
    public function __construct(
        private iterable $marshallingStrategies,
        private EncoderFactoryInterface $encoderFactory,
    ) {
    }

    public function create(OutputInterface $output): Marshaller
    {
        return new Marshaller($this->marshallingStrategies, $this->encoderFactory->create($output));
    }
}
