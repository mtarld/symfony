<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Attribute\Warmable;

#[Warmable]
final class Dto
{
    #[Formatter([self::class, 'multiplyAndCast'])]
    public int $int = 12;

    #[Name('test')]
    public ?string $string = null;

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
