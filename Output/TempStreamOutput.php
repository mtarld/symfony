<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

final class TempStreamOutput extends StreamOutput
{
    public function __construct(int $memoryThreshold = 2048)
    {
        parent::__construct(sprintf('php://temp/maxmemory:%d', $memoryThreshold));
    }
}
