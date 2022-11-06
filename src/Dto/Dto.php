<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Name;

final class Dto
{
    #[Name('theint')]
    public int $int = 1;

    #[Formatter([self::class, 'formatInt'])]
    public string $string = 'thestring';

    // public Dto2 $object;

    /** @var array<string, Dto> */
    public array $array = [1, 2];

    public function __construct()
    {
        $this->object = new Dto2();
    }

    public static function formatInt(string $value): Dto2
    {
        return new Dto2();
    }
}
