<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

final class DummyWithNotPublicProperty
{
    public int $id;

    private string $name;
}
