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

use Symfony\Component\SerDes\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    /**
     * @param callable|null $onSerialize
     * @param callable|null $onDeserialize
     */
    public function __construct(
        public readonly mixed $onSerialize = null,
        public readonly mixed $onDeserialize = null,
    ) {
        if (null !== $onSerialize && !\is_callable($onSerialize)) {
            throw new InvalidArgumentException(sprintf('Parameter "$onSerialize" of attribute "%s" must be a valid callable.', self::class));
        }

        if (null !== $onDeserialize && !\is_callable($onDeserialize)) {
            throw new InvalidArgumentException(sprintf('Parameter "$onDeserialize" of attribute "%s" must be a valid callable.', self::class));
        }
    }
}
