<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class MemoryStream extends Stream
{
    public function __construct(string $mode = 'w+b')
    {
        parent::__construct('php://memory', $mode);
    }
}
