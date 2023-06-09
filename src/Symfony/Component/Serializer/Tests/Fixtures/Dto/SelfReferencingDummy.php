<?php

namespace Symfony\Component\Serializer\Tests\Fixtures\Dto;

class SelfReferencingDummy
{
    public SelfReferencingDummy $self;
}
