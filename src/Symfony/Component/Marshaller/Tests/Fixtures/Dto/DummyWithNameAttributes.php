<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Attribute\Name;

#[Marshallable]
class DummyWithNameAttributes
{
    #[Name('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
