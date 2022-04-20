<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Input\InputInterface;

interface UnmarshallerInterface
{
    public function unmarshal(InputInterface $input, string $type): mixed;
}
