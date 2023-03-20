<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class CircularReferencingDummyLeft
{
    public CircularReferencingDummyRight $right;
}
