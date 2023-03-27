<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class CircularReferencingDummyRight
{
    public CircularReferencingDummyLeft $left;
}
