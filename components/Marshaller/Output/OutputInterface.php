<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

interface OutputInterface
{
    public function write(string $data): void;
}
