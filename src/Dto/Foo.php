<?php

declare(strict_types=1);

namespace App\Dto;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Exporter\ChainExporter;
use App\Serializer\Output\OutputInterface;
use App\Serializer\SerializableInterface;

final class Foo implements SerializableInterface
{
    public string $name;
    public int $bar = 123;
    public array $baz;

    public function serialize(EncoderInterface $encoder, ChainExporter $serializer): OutputInterface
    {
        $generator = function (): iterable {
            yield 'name' => 'foo';
            yield 'bar' => $this->bar;
            yield 'baz' => [1, '2', 3];
            yield 'aze' => ['a' => 'b'];
        };

        $encoder->encodeDict($generator, $serializer);

        return $encoder->getOutput();
    }
}
