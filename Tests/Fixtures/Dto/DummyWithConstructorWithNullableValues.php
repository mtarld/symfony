<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class DummyWithConstructorWithNullableValues
{
    public function __construct(
        public ?int $id,
    ) {
    }
}
