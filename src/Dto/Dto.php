<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Warmable;

#[Warmable]
final class Dto extends OneAbstract
{
    public int $int = 12;
    public ?\Stringable $string = null;

    /**
     * @var Dto2
     */
    public object $object;

    /**
     * @var array<int, string>
     */
    public array $array = [];

    public function __construct()
    {
        $this->object = new Dto2();
    }

    public static function multiplyAndCast(int $value, array $context): string
    {
        return (string) (2 * $value);
    }
}
