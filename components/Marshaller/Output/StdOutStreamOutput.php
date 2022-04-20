<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

final class StdOutStreamOutput implements OutputInterface, StreamOutputInterface
{
    use StreamOutputTrait;

    public function __construct()
    {
        $this->filename = 'php://stdout';
    }
}
