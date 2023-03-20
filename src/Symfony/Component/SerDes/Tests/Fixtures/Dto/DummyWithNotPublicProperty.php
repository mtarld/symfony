<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class DummyWithNotPublicProperty
{
    public int $id;

    private string $name;
}
