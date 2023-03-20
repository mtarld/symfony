<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class DummyWithConstructorWithRequiredValues
{
    public int $id = 1;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
