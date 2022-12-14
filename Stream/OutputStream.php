<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class OutputStream extends Stream
{
    public function __construct()
    {
        parent::__construct('php://output', readable: true, writable: false);
    }
}
