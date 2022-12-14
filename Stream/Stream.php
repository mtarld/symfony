<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

abstract class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $stream;

    protected function __construct(
        protected readonly string $filename,
        protected readonly string $mode,
    ) {
    }

    final public function stream()
    {
        if (null !== $this->stream) {
            return $this->stream;
        }

        if (false === $stream = fopen($this->filename, $this->mode)) {
            throw new \RuntimeException(sprintf('Cannot open "%s" stream', $this->filename));
        }

        return $this->stream = $stream;
    }

    final public function __toString(): string
    {
        rewind($this->stream());

        if (false === $content = stream_get_contents($this->stream())) {
            throw new \RuntimeException(sprintf('Cannot read "%s" stream', $this->filename));
        }

        return $content;
    }
}
