<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Warmable;

#[Warmable(nullable: false)]
final class WarmableNotNullableDummy
{
}
