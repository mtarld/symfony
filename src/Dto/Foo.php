<?php

namespace App\Dto;

use App\Serializer\Serializable;

final class Foo implements Serializable
{
    public string $name;
    public int $bar = 123;
    public array $baz;

    public function normalize(): iterable
    {
        yield 'name' => 'caca';
        yield 'bar' => $this->bar;
        yield 'baz' => [1, '2', 3];
        yield 'aze' => ['a' => 'b'];
    }
}
