<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Context\OptionInterface;

final class DepthOption implements OptionInterface
{
    public function __construct(
        public readonly int $maxDepth,
        public readonly bool $rejectCircularReference,
    ) {
        if ($maxDepth < 0) {
            throw new \InvalidArgumentException(sprintf('"%s::$maxDepth" must be positive.', self::class));
        }
    }

    public function mergeNativeContext(array $nativeContext): array
    {
        return $nativeContext += [
            'max_depth' => $this->maxDepth,
            'reject_circular_reference' => $this->rejectCircularReference,
        ];
    }
}
