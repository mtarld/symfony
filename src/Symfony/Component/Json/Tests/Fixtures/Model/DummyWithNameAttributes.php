<?php

namespace Symfony\Component\Json\Tests\Fixtures\Model;

use Symfony\Component\Encoder\Attribute\EncodedName;

class DummyWithNameAttributes
{
    #[EncodedName('@id')]
    public int $id = 1;

    public string $name = 'dummy';
}
