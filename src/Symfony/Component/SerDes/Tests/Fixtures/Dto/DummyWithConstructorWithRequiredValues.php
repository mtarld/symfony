<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class DummyWithConstructorWithRequiredValues
{
    public int $id = 1;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
