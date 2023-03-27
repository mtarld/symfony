<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class DummyWithConstructorWithNullableValues
{
    public function __construct(
        public ?int $id,
    ) {
    }
}
