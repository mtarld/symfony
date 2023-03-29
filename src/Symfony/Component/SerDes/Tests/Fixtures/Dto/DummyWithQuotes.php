<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\SerializedName;

class DummyWithQuotes
{
    #[SerializedName('"name"')]
    public string $name = '"quoted" dummy';
}
