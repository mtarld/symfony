<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Name;

class DummyWithNameAttributes
{
    #[Name('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
