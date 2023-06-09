<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class DummyWithConstructorWithNullableValues
{
    public function __construct(
        public ?int $id,
    ) {
    }
}
