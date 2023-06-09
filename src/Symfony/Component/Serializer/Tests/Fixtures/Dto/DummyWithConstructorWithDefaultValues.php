<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class DummyWithConstructorWithDefaultValues
{
    public function __construct(
        public int $id = 1,
    ) {
    }
}
