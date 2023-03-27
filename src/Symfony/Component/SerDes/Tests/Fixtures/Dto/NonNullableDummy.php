<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Nullable;

#[Nullable(nullable: false)]
class NonNullableDummy
{
}
