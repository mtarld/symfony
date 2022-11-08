<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Name;

final class Dto
{
    #[Name('theint')]
    public int|string|null $int = 12;
    //
    // #[Formatter([self::class, 'formatInt'])]
    // public string $string = 'thestring';

    public ?Dto2 $object = null;

    // /** @var array<string, Dto2>|null */
    // public ?array $array = [];

    public function __construct()
    {
        // $this->array = ['foo' => new Dto2()];
    }

    /**
     * @return Dto2
     */
    public static function formatInt(string $value): Dto2
    {
        return new Dto2();
    }
}
