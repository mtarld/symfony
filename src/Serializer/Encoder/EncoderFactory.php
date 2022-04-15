<?php

declare(strict_types=1);

namespace App\Serializer\Encoder;

use App\Serializer\Output\OutputInterface;

final class EncoderFactory
{
    public function __construct(
        /** @var iterable<EncoderFactoryInterface> */
        private iterable $encoderFactories,
    ) {
    }

    public function create(string $format, OutputInterface $output): EncoderInterface
    {
        foreach ($this->encoderFactories as $factory) {
            if ($factory->supports($format)) {
                return $factory->create($output);
            }
        }

        throw new \RuntimeException('Missing encoder');
    }
}
