<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Stream\StreamInterface;

interface MarshallerInterface
{
    public function marshal(mixed $data, string $format, StreamInterface $output, Context $context = null): void;

    public function generate(string $type, string $format, Context $context = null): string;

    public function unmarshal(StreamInterface $input, string $type, string $format, Context $context = null): mixed;
}
