<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class CircularReferencingDummyRight
{
    public CircularReferencingDummyLeft $left;
}
