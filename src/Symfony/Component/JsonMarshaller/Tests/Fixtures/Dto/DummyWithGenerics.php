<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

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
