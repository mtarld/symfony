<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;
use Symfony\Component\SerDes\Attribute\Name;

#[Serializable]
class AnotherDummyWithNameAttributes
{
    public int $id = 1;

    #[Name('call_me_with')]
    public string $name = 'dummy';
}
