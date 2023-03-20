<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

/**
 * @template T of object
 */
#[Serializable]
class DummyWithGenerics
{
    /**
     * @var array<int, T>
     */
    public array $dummies = [];
}
