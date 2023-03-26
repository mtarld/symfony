<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Attribute;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    /**
     * @param callable(mixed, array<string, mixed>=): mixed|null $onSerialize
     * @param callable(mixed, array<string, mixed>=): mixed|null $onDeserialize
     */
    public function __construct(
        public readonly mixed $onSerialize = null,
        public readonly mixed $onDeserialize = null,
    ) {
    }
}
