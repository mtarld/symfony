<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Groups;

class DummyWithGroups
{
    public string $none = 'none';

    #[Groups('one')]
    public string $one = 'one';

    #[Groups(['one', 'two'])]
    public string $oneAndTwo = 'oneAndTwo';

    #[Groups(['two', 'three'])]
    public string $twoAndThree = 'twoAndThree';
}
