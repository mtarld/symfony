<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Attribute\Name;

#[Marshallable]
final class DummyWithQuotes
{
    #[Name('"name"')]
    public string $name = '"quoted" dummy';
}
