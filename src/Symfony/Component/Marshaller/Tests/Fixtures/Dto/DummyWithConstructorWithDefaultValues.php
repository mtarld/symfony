<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class DummyWithConstructorWithDefaultValues
{
    public function __construct(
        public int $id = 1,
    ) {
    }
}
