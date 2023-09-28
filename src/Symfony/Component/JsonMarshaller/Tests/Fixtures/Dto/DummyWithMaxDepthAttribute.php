<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

use Symfony\Component\JsonMarshaller\Attribute\MaxDepth;

class DummyWithMaxDepthAttribute
{
    #[MaxDepth(2, [self::class, 'boolean'])]
    public int $id = 1;

    public static function boolean(int $value): bool
    {
        return false;
    }
}
