<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

final class CircularReferencingDummyLeft
{
    public CircularReferencingDummyRight $right;
}
