<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Encoder\EncoderFactoryInterface;
use Symfony\Component\Marshaller\Marshalling\Marshaller as MarshallingMarshaller;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    public function __construct(
        private iterable $marshallingStrategies,
        private EncoderFactoryInterface $encoderFactory,
    ) {
    }

    public function marshal(mixed $data, OutputInterface $output): void
    {
        $marshaller = new MarshallingMarshaller($this->marshallingStrategies, $this->encoderFactory->create($output));
        $marshaller->marshal($data);
    }
}
