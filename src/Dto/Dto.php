<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Name;

final class Dto
{
    #[Name('theint')]
    public ?int $int = 12;

    #[Formatter('strtoupper')]
    public string $string = 'thestring';

    // private Dto2 $object;

    /** @var array<int, App\Dto\Dto2>|null */
    public ?array $array = [];

    public function __construct()
    {
        // $this->array = ['foo' => new Dto2()];
    }

    public static function formatInt(string $value): Dto2
    {
        return new Dto2();
    }
}
