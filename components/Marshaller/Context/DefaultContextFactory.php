<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

final class DefaultContextFactory
{
    public function create(): Context
    {
        return new Context();
    }
}
