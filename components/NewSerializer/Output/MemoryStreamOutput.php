<?php

namespace Symfony\Component\NewSerializer\Output;

final class MemoryStreamOutput implements OutputInterface
{
    /**
     * @var resource
     */
    private $stream;

    private readonly string $filename;

    public function __construct()
    {
        $this->filename = 'php://memory';
    }

    public function write(string $value): void
    {
        fwrite($this->getOrCreateStream(), $value);
    }

    public function erase(int $count): void
    {
        ftruncate($this->getOrCreateStream(), fstat($this->getOrCreateStream())['size'] - $count);
    }

    public function __toString(): string
    {
        rewind($this->getOrCreateStream());

        return stream_get_contents($this->getOrCreateStream());
    }

    /**
     * @return stream
     */
    private function getOrCreateStream()
    {
        if (null === $this->stream) {
            $this->stream = fopen($this->filename, 'wb');
        }

        return $this->stream;
    }
}

