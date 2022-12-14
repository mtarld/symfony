<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class StdoutStream extends Stream
{
    public function __construct()
    {
        parent::__construct('php://stdout', readable: true, writable: false);
    }
}
