<?php

declare(strict_types=1);

namespace App\Dto;

final class Dto
{
    #[\MarshalName('test')]
    #[\MarshalFormatter([self::class, 'multiplyAndCast'])]
    public int $int = 12;

    /**
     * @var Dto2|null
     */
    public object $object;

    // /**
    //  * @var array<array<string, list<bool|null>>>
    //  */
    // public array $string = [];

    public function __construct()
    {
        $this->object = new Dto2();
    }

    /**
     * @return Dto2|null
     */
    public static function multiplyAndCast(int $value, array $context): object
    {
        return new Dto2();

        return (string) (2 * $value);
    }
}
