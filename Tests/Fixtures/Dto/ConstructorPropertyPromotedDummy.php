<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

#[Marshallable]
final class ConstructorPropertyPromotedDummy
{
    public function __construct(
        public int $id,
    ) {
    }
}
