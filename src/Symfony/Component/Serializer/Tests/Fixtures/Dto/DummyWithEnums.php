<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Tests\Fixtures\Enum\DummyBackedEnum;

final class DummyWithEnums
{
    public DummyBackedEnum $intEnum = DummyBackedEnum::ONE;
}
