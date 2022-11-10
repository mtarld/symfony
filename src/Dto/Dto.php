<?php

declare(strict_types=1);

namespace App\Dto;

final class Dto
{
    public int $int = 12;
    public ?Dto2 $object = null;
    public ?Dto2 $object2 = null;

    public function __construct()
    {
        $this->object = new Dto2();
    }
}
