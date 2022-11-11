<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * @template T of Dto2
 */
final class Dto
{
    #[\MarshalName('test')]
    #[\MarshalFormatter([self::class, 'multiplyAndCast'])]
    public int $int = 12;
    //
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

    /**
     * @return Dto2
     */
    public static function multiplyAndCast(int $value, array $context): ?object
    {
        return null;

        return (string) (2 * $value);
    }
}
