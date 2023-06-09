<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

class AnotherDummyWithNameAttributes
{
    public int $id = 1;

    #[SerializedName('call_me_with')]
    public string $name = 'dummy';
}
