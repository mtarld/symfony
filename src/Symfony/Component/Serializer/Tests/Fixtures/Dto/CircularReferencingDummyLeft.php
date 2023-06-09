<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class CircularReferencingDummyLeft
{
    public CircularReferencingDummyRight $right;
}
