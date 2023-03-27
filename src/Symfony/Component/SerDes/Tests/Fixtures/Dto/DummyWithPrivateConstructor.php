<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class DummyWithPrivateConstructor
{
    public int $id = 1;

    private function __construct()
    {
        $this->id = 2;
    }
}
