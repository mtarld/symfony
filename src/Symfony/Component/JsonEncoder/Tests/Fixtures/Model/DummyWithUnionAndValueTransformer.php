<?php

namespace Symfony\Component\JsonEncoder\Tests\Fixtures\Model;

use Symfony\Component\JsonEncoder\Attribute\ValueTransformer;

class DummyWithUnionAndValueTransformer
{
    public \DateTimeInterface|bool|string $foo;

    // #[ValueTransformer(
    //     toNativeValue: [self::class, 'toDateTime'],
    //     // toJsonValue: [self::class, 'convertDateTime'],
    // )]
    // public \DateTimeInterface $bar;
    //
    // public static function convertDateTime(string $value): \DateTimeInterface
    // {
    //     dd($value);
    // }
}
