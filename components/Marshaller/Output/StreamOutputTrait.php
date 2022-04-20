<?php

namespace Symfony\Component\Marshaller\Output;

trait StreamOutputTrait
{
    /**
     * @var resource
     */
    private $stream;

    private readonly string $filename;

    public function write(string $value): void
    {
        fwrite($this->getOrCreateStream(), $value);
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

    /**
     * @return stream
     */
    public function getStream()
    {
        return $this->getOrCreateStream();
    }
}
