<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Name;

final class Item
{
    #[Name('@id')]
    public int $id = 1;

    public string $name = 'name';
}
