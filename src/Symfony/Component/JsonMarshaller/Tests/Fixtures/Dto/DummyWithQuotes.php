<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

use Symfony\Component\JsonMarshaller\Attribute\MarshalledName;

class DummyWithQuotes
{
    #[MarshalledName('"name"')]
    public string $name = '"quoted" dummy';
}
