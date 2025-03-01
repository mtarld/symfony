<?php

namespace Symfony\Component\JsonStreamer\Tests\Fixtures\Model;

class DummyWithDateTimes
{
    public \DateTimeInterface|bool $interface;
    public \DateTimeImmutable|bool $immutable;
}
