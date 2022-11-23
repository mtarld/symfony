<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

use Symfony\Component\Marshaller\Attribute\Name;

final class DummyWithNameAttributes
{
    #[Name('@id')]
    public int $id = 1;

    public string $name = 'dummy';

    #[Name('active')]
    public bool $enabled = true;
}
