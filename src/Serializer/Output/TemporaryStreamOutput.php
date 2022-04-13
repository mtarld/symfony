<?php

namespace App\Serializer\Output;

final class TemporaryStreamOutput implements Output
{
    /**
     * @var resource
     */
    private $stream;

    public function __construct()
    {
        // $this->stream = fopen('php://temp/', 'wb');
        $this->stream = fopen('/tmp/foo', 'wb');
    }

    public function write(string $value): void
    {
        fwrite($this->stream, $value);
    }

    public function erase(int $count): void
    {
        sleep(1);
        ftruncate($this->stream, fstat($this->stream)['size'] - $count);
    }

    public function __toString(): string
    {
        rewind($this->stream);

        return stream_get_contents($this->stream);
    }
}

