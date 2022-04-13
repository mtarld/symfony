<?php

declare(strict_types=1);

namespace App\Serializer\Encoder;

use App\Serializer\Output\Output;
use App\Serializer\Serializer;

final class JsonEncoder implements Encoder
{
    private Serializer|null $serializer = null;
    private Output|null $output = null;

    public function encodeInt(int $value): void
    {
        $this->write((string) $value);
    }

    public function encodeString(string $value): void
    {
        $this->write(sprintf('"%s"', $value));
    }

    public function encodeDict(\Closure $generator): void
    {
        $this->write('{');

        foreach ($generator() as $key => $value) {
            $this->write(sprintf('"%s":', $key));
            $this->serialize($value);
            $this->write(',');
        }

        $this->erase(1);
        $this->write('}');
    }

    public function encodeList(\Closure $generator): void
    {
        $this->write('[');

        foreach ($generator() as $value) {
            $this->serialize($value);
            $this->write(',');
        }

        $this->erase(1);
        $this->write(']');
    }

    public function withSerializer(Serializer $serializer): static
    {
        $clone = clone $this;
        $clone->serializer = $serializer;

        return $clone;
    }

    public function withOutput(Output $output): static
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

    private function serialize(mixed $value): void
    {
        if (!$this->serializer) {
            throw new \RuntimeException('Missing serializer');
        }

        $this->serializer->serialize($value);
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
