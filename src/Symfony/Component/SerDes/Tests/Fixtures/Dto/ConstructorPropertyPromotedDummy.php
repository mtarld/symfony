<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class ConstructorPropertyPromotedDummy
{
    public function __construct(
        public int $id,
    ) {
    }
}
