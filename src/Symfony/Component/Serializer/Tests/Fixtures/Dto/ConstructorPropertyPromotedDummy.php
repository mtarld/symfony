<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class ConstructorPropertyPromotedDummy
{
    public function __construct(
        public int $id,
    ) {
    }
}
