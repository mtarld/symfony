<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Output\OutputInterface;

interface MarshallerInterface
{
    public function marshal(object $object, string $format, OutputInterface $output, Context $context = null): void;
}
