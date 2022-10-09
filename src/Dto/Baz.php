<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Groups;

final class Baz
{
    #[Groups('anotherGroup')]
    public int $foo = 0;
}
