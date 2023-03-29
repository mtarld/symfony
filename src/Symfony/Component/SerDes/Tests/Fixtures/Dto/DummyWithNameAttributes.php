<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\SerializedName;

class DummyWithNameAttributes
{
    #[SerializedName('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
