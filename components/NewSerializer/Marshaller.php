<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer;

use Symfony\Component\NewSerializer\Marshalling\MarshallerFactory;
use Symfony\Component\NewSerializer\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    public function __construct(
        private MarshallerFactory $marshallerFactory,
    ) {
    }

    public function marshal(mixed $value, OutputInterface $output): void
    {
        $this->marshallerFactory->create($output)->marshal($value);
    }
}
