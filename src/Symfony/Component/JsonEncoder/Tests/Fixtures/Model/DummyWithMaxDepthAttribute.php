<?php

namespace Symfony\Component\JsonEncoder\Tests\Fixtures\Model;

use Symfony\Component\JsonEncoder\Attribute\MaxDepth;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\DoubleIntAndCastToStringNormalizer;

class DummyWithMaxDepthAttribute
{
    #[MaxDepth(2, DoubleIntAndCastToStringNormalizer::class)]
    public int $id = 1;
}
