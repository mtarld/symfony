<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

// TODO test me in E2E (marshal_generate test) - and don't forget union
final class SelfReferencingDummy
{
    public SelfReferencingDummy $self;
}
