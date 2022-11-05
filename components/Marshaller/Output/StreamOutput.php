<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

abstract class StreamOutput implements OutputInterface
{
    /**
     * @var resource
     */
    protected $stream;

    public function __construct(
        protected readonly string $filename,
    ) {
    }

    final public function stream()
    {
        if (null === $this->stream) {
            $this->stream = fopen($this->filename, 'wb');
        }

        return $this->stream;
    }

    final public function __toString(): string
    {
        rewind($this->stream());

        return stream_get_contents($this->stream());
    }
}
