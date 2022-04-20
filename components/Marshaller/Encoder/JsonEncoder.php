<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Encoder;

use Symfony\Component\Marshaller\Output\OutputInterface;

final class JsonEncoder implements EncoderInterface
{
    public function __construct(
        private OutputInterface $output,
    ) {
    }

    public function encodeInt(int $int): void
    {
        $this->output->write((string) $int);
    }

    public function encodeString(string $string): void
    {
        $this->output->write(sprintf('"%s"', $string));
    }

    public function encodeDict(\Generator $dict, \Closure $marshal): void
    {
        $this->output->write('{');

        $valid = $dict->valid();

        while ($valid) {
            $this->output->write(sprintf('"%s":', $dict->key()));
            $marshal($dict->current());

            $dict->next();
            if ($valid = $dict->valid()) {
                $this->output->write(',');
            }
        }

        $this->output->write('}');
    }

    public function encodeList(\Generator $list, \Closure $marshal): void
    {
        $this->output->write('[');

        $valid = $list->valid();

        while ($valid) {
            $this->output->write(sprintf('"%s":', $list->key()));
            $marshal($list->current());

            $list->next();
            if ($valid = $list->valid()) {
                $this->output->write(',');
            }
        }

        $this->output->write(']');
    }
}
