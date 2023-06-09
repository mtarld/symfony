<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class CircularReferencingDummyRight
{
    public CircularReferencingDummyLeft $left;
}
