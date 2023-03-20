<?php

namespace Symfony\Component\Encoder\Tests\Fixtures\Model;

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
