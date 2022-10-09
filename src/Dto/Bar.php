<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Groups;
use Symfony\Component\Marshaller\Attribute\Name;

final class Bar
{
    // #[Name('barValue')]
    // #[Groups('barGroup')]
    // public int $value = 12;

    /** @var list<Foo|null> */
    public array $foos = [];

    public function __construct()
    {
        // $this->baz = new Baz();
    }
}
