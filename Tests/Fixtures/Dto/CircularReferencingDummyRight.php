<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
final class CircularReferencingDummyRight
{
    public CircularReferencingDummyLeft $left;
}
