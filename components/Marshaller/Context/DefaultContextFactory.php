<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

use Symfony\Component\Marshaller\Context\Option\DepthOption;

final class DefaultContextFactory
{
    public function __construct(
        private readonly int $maxDepth,
        private readonly bool $rejectCircularReference,
    ) {
    }

    public function create(): Context
    {
        $depthOption = new DepthOption($this->maxDepth, $this->rejectCircularReference);

        return new Context($depthOption);
    }
}
