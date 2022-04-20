<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Input;

final class StringInput implements InputInterface
{
    public function __construct(
        private string $string,
        private int $chunkSize = 1024
    ) {
    }

    public function getIterator(): \Generator
    {
        $length = strlen($this->string);

        for ($offset = 0; $offset < $length; $offset += $this->chunkSize) {
            yield substr($this->string, $offset, $this->chunkSize);
        }
    }
}
