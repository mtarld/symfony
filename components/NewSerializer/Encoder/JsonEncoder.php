<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Encoder;

use Symfony\Component\NewSerializer\Output\OutputInterface;

final class JsonEncoder implements EncoderInterface
{
    public function __construct(
        private OutputInterface $output,
    ) {
    }

    public function encodeInt(int $value): void
    {
        $this->output->write((string) $value);
    }

    public function encodeString(string $value): void
    {
        $this->output->write(sprintf('"%s"', $value));
    }

    public function encodeDict(\Closure $generator, \Closure $serialize): void
    {
        $this->output->write('{');

        foreach ($generator() as $key => $value) {
            $this->output->write(sprintf('"%s":', $key));
            $serialize($value);
            $this->output->write(',');
        }

        $this->output->erase(1);
        $this->output->write('}');
    }

    public function encodeList(\Closure $generator, \Closure $serialize): void
    {
        $this->output->write('[');

        foreach ($generator() as $value) {
            $serialize($value);
            $this->output->write(',');
        }

        $this->output->erase(1);
        $this->output->write(']');
    }

    public function getOutput(): OutputInterface|null
    {
        return $this->output;
    }

    public function supports(string $format): bool
    {
        return 'json' === $format;
    }
}
