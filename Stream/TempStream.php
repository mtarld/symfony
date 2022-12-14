<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class TempStream extends Stream
{
    public function __construct(int $memoryThreshold = 2048, string $mode = 'w+b')
    {
        parent::__construct(sprintf('php://temp/maxmemory:%d', $memoryThreshold), $mode);
    }
}
