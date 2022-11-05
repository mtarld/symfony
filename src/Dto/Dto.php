<?php

declare(strict_types=1);

namespace App\Dto;

final class Dto
{
    public int|string $int = 1;
    public string $string = 'thestring';
    public Dto2 $object;
    public array $array = [1, 2];

    public function __construct()
    {
        $this->object = new Dto2();
    }
}
