<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class DummyWithConstructorWithRequiredValues
{
    public int $id = 1;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
