<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Warmable;

#[Warmable]
final class Dto
{
    #[\MarshalName('test')]
    #[\MarshalFormatter([self::class, 'multiplyAndCast'])]
    public int $int = 12;

    // /**
    //  * @var Dto2|null
    //  */
    // public object $object;

    // /**
    //  * @var array<array<string, list<bool|null>>>
    //  */
    // public array $string = [];

    public function __construct()
    {
        $this->object = new Dto2();
    }

    public static function multiplyAndCast(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
