<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
class ClassicDummy
{
    public int $id = 1;
    public string $name = 'dummy';
}
