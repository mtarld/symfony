<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Attribute;

/**
 * Defines the maximum encoding depth for the property.
 *
 * When the maximum depth is reached, a {@see Symfony\Component\JsonEncoder\Encode\Normalizer\NormalizerInterface} service is called if its id has been defined.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class MaxDepth
{
    /**
     * @param positive-int $maxDepth
     */
    public function __construct(
        public int $maxDepth,
        private ?string $maxDepthReachedNormalizerServiceId = null,
    ) {
    }

    /**
     * @return positive-int
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    public function getMaxDepthReachedNormalizerServiceId(): ?string
    {
        return $this->maxDepthReachedNormalizerServiceId;
    }
}
