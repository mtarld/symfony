<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Encoder;

use Symfony\Component\NewSerializer\Output\OutputInterface;

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
