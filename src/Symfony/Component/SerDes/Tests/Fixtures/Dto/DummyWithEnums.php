<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;

final class DummyWithEnums
{
    public DummyBackedEnum $intEnum = DummyBackedEnum::ONE;
}
