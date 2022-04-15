<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;
use Symfony\Component\NewSerializer\SerializableInterface;

final class Foo implements SerializableInterface
{
    public string $name;
    public int $bar = 123;
    public array $baz;

    public function serialize(EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        $generator = function (): iterable {
            yield 'name' => 'foo';
            yield 'bar' => $this->bar;
            yield 'baz' => [1, '2', 3];
            yield 'aze' => ['a' => 'b'];
        };

        $encoder->encodeDict($generator, $serialize);

        return $encoder->getOutput();
    }
}
