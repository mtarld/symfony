<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class DummyWithNotPublicProperty
{
    public int $id;

    private string $name;
}
