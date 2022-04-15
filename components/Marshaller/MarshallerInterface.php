<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Output\OutputInterface;

interface MarshallerInterface
{
    public function marshal(mixed $data, OutputInterface $output): void;
}
