<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class DummyWithNotPublicProperty
{
    public int $id;

    private string $name;
}
