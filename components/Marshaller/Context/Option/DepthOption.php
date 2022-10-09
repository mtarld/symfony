<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Context\OptionInterface;

final class DepthOption implements OptionInterface
{
    public function __construct(
        public readonly int $depth,
        public readonly bool $rejectCircularReference,
    ) {
        if ($depth < 0) {
            throw new \InvalidArgumentException('TODO');
        }
    }

    public function signature(): string
    {
        return sprintf('%s-%s', (string) $this->depth, $this->rejectCircularReference ? 'true' : 'false');
    }
}
