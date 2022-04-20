<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

final class TempStreamOutput implements OutputInterface, StreamOutputInterface, \Stringable
{
    use StreamOutputTrait;

    public function __construct(int $fileWriteMemoryThreshold = 2048)
    {
        $this->filename = sprintf('php://temp/maxmemory:%d', $fileWriteMemoryThreshold);
    }

    public function __toString(): string
    {
        rewind($this->getOrCreateStream());

        return stream_get_contents($this->getOrCreateStream());
    }
}
