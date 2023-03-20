<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class DummyWithPrivateConstructor
{
    public int $id = 1;

    private function __construct()
    {
        $this->id = 2;
    }
}
