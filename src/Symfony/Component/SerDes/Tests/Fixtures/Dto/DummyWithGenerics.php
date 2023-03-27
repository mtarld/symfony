<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

/**
 * @template T of object
 */
class DummyWithGenerics
{
    /**
     * @var array<int, T>
     */
    public array $dummies = [];
}
