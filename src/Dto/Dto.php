<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Warmable;

#[Warmable]
final class Dto
{
    // public int $int = 12;
    public ?\Stringable $string = null;

    // /**
    //  * @var Dto2|null
    //  */
    // public object $object;

    // /**
    //  * @var array<int, string>
    //  */
    // public array $string = ['o' => true];

    public function __construct()
    {
        $this->object = new Dto2();
    }

    public static function multiplyAndCast(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
