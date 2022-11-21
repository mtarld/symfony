<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Attribute\Warmable;

function test(int $value, array $context): string
{
    return (string) (2 * $value);
}

#[Warmable]
final class Dto
{
    #[Name('@id')]
    // #[Formatter([self::class, 'multiplyAndCast'])]
    #[Formatter('App\\Dto\\test')]
    public int $id = 12;

    // public ?string $string = null;
    //
    // /**
    //  * @var Dto2
    //  */
    // public object $object;

    // /**
    //  * @var list<object>
    //  */
    // public array $resources = [];

    // /**
    //  * @var array<int, string>
    //  */
    // public array $array = [];

    public function __construct()
    {
        // $this->object = new Dto2();
    }

    public static function multiplyAndCast(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
