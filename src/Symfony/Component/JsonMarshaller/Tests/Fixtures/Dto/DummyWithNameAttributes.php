<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

use Symfony\Component\JsonMarshaller\Attribute\MarshalledName;

class DummyWithNameAttributes
{
    #[MarshalledName('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
