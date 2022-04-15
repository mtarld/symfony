<?php

namespace Symfony\Component\Marshaller\Output;

final class StringOutput implements OutputInterface
{
    private string $data = '';

    public function write(string $value): void
    {
        $this->data .= $value;
    }

    public function erase(int $count): void
    {
        $this->data = substr_replace($this->data , '', -$count);
    }

    public function __toString(): string
    {
        return $this->data;
    }
}

