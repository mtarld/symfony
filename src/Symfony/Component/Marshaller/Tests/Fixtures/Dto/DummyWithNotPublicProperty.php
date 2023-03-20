<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class DummyWithNotPublicProperty
{
    public int $id;

    private string $name;
}
