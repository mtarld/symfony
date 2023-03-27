<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Name;

class DummyWithQuotes
{
    #[Name('"name"')]
    public string $name = '"quoted" dummy';
}
