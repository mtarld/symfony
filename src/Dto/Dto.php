<?php

declare(strict_types=1);

namespace App\Dto;

final class Dto
{
    #[\MarshalName('test')]
    #[\MarshalFormatter([Dto2::class, 'multiplyAndCast'])]
    public int $int = 12;

    public string $string = 's';

    public Dto2 $object;
    public ?Dto2 $object2 = null;

    public function __construct()
    {
        $this->object = new Dto2();
    }

    public static function multiplyAndCast(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
