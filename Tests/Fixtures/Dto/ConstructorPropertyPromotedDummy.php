<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

final class ConstructorPropertyPromotedDummy
{
    public function __construct(
        public int $id,
    ) {
    }
}
