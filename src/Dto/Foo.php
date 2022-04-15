<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Encoder\EncoderInterface;
use Symfony\Component\Marshaller\Marshalling\MarshallableInterface;

final class Foo implements MarshallableInterface
{
    public string $name;
    public int $bar = 123;
    public array $baz;

    public function marshal(EncoderInterface $encoder, \Closure $marchal): void
    {
        $generator = function (): iterable {
            yield 'name' => 'foo';
            yield 'bar' => $this->bar;
            yield 'baz' => [1, '2', 3];
            yield 'aze' => ['a' => 'b'];
        };

        $encoder->encodeDict($generator, $marchal);
    }
}
