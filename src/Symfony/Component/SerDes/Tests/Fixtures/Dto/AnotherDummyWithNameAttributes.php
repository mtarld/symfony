<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\SerializedName;

class AnotherDummyWithNameAttributes
{
    public int $id = 1;

    #[SerializedName('call_me_with')]
    public string $name = 'dummy';
}
