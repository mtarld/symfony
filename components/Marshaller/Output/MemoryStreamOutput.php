<?php

namespace Symfony\Component\Marshaller\Output;

final class MemoryStreamOutput implements OutputInterface, StreamOutputInterface, \Stringable
{
    use StreamOutputTrait;

    public function __construct()
    {
        $this->filename = 'php://memory';
    }

    public function __toString(): string
    {
        rewind($this->getOrCreateStream());

        return stream_get_contents($this->getOrCreateStream());
    }
}

