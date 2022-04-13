<?php

declare(strict_types=1);

namespace App\Serializer\Encoder;

use App\Serializer\Output\Output;
use App\Serializer\Serializer;

final class JsonEncoder implements Encoder
{
    private Output|null $output = null;
    private Serializer|null $serializer = null;

    public function encodeInt(int $value): void
    {
        $this->write((string) $value);
    }

    public function encodeString(string $value): void
    {
        $this->write(sprintf('"%s"', $value));
    }

    public function encodeDict(\Closure $generator, Serializer $serializer): void
    {
        $this->write('{');

        foreach ($generator() as $key => $value) {
            $this->write(sprintf('"%s":', $key));
            $serializer->serialize($value);
            $this->write(',');
        }

        $this->erase(1);
        $this->write('}');
    }

    public function encodeList(\Closure $generator, Serializer $serializer): void
    {
        $this->write('[');

        foreach ($generator() as $value) {
            $serializer->serialize($value);
            $this->write(',');
        }

        $this->erase(1);
        $this->write(']');
    }

    public function forOutput(Output $output): static
    {
        $clone = clone $this;
        $clone->output = $output;

        return $clone;
    }

    public function getOutput(): Output|null
    {
        return $this->output;
    }

    public function supports(string $format): bool
    {
        return 'json' === $format;
    }

    private function write(string $value): void
    {
        if (!$this->output) {
            throw new \RuntimeException('Missing stream');
        }

        $this->output->write($value);
    }

    private function erase(int $count): void
    {
        if (!$this->output) {
            throw new \RuntimeException('Missing stream');
        }

        $this->output->erase($count);
    }
}
