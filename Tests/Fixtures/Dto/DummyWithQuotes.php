<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Name;

final class DummyWithQuotes
{
    #[Name('"name"')]
    public string $name = '"quoted" dummy';
}
