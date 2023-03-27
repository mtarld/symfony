<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class CircularReferencingDummyLeft
{
    public CircularReferencingDummyRight $right;
}
