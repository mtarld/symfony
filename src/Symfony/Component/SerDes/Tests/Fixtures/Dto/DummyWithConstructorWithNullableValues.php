<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

#[Serializable]
class DummyWithConstructorWithNullableValues
{
    public function __construct(
        public ?int $id,
    ) {
    }
}
