<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class DummyWithConstructorWithDefaultValues
{
    public function __construct(
        public int $id = 1,
    ) {
    }
}
