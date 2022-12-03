<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

final class MemoryStreamOutput extends StreamOutput
{
    public function __construct()
    {
        parent::__construct('php://memory');
    }
}
