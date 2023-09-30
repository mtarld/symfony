<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

final class DummyWithOtherDummies
{
    public string $name;
    public DummyWithNameAttributes $otherDummyOne;
    public ClassicDummy $otherDummyTwo;
}
