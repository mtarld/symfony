<?php

declare(strict_types=1);

namespace App\Dto;

final class Dto2 extends ThreeAbstract
{
    public float $float = 1.23;
    public bool $bool = false;

    public static function multiplyAndCast(int $value, array $context): self
    {
        $self = new self();
        $self->bool = false;

        return $self;
    }
}
