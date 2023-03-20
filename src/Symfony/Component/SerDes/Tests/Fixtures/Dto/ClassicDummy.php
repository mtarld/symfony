<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class ClassicDummy
{
    public int $id = 1;
    public string $name = 'dummy';
}
