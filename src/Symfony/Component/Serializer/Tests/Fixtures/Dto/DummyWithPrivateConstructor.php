<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class DummyWithPrivateConstructor
{
    public int $id = 1;

    private function __construct()
    {
        $this->id = 2;
    }
}
