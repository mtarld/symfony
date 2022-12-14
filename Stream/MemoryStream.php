<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class MemoryStream extends Stream
{
    public function __construct()
    {
        parent::__construct('php://memory', readable: true, writable: true);
    }
}
