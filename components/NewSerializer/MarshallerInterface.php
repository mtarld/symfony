<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer;

use Symfony\Component\NewSerializer\Output\OutputInterface;

interface MarshallerInterface
{
    public function marshal(mixed $value, OutputInterface $output): void;
}
