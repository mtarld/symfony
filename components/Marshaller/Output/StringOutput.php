<?php

namespace Symfony\Component\Marshaller\Output;

final class StringOutput implements OutputInterface, \Stringable
{
    private string $data = '';

    public function write(string $value): void
    {
        $this->data .= $value;
    }

    public function __toString(): string
    {
        return $this->data;
    }
}

