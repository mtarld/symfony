<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class DummyWithConstructorWithDefaultValues
{
    public function __construct(
        public int $id = 1,
    ) {
    }
}
