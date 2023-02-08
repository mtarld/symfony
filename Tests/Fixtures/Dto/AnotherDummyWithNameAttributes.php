<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Attribute\Name;

#[Marshallable]
final class AnotherDummyWithNameAttributes
{
    public int $id = 1;

    #[Name('call_me_with')]
    public string $name = 'dummy';
}
