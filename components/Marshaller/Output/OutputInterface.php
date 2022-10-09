<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

interface OutputInterface extends \Stringable
{
    public function write(string $data): void;

    /**
     * @return resource
     */
    public function stream();
}
