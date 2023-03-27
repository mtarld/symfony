<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

class SelfReferencingDummy
{
    public SelfReferencingDummy $self;
}
