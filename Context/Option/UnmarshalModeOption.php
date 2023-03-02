<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class UnmarshalModeOption
{
    public const EAGER = 'eager';
    public const LAZY = 'lazy';

    public function __construct(
        public readonly string $mode,
    ) {
        if (!\in_array($mode, [self::EAGER, self::LAZY])) {
            throw new InvalidArgumentException(sprintf('Mode "%s" is invalid, allowed values are "%s" or "%s".', $mode, self::EAGER, self::LAZY));
        }
    }

    public static function eager(): self
    {
        return new self(self::EAGER);
    }

    public static function lazy(): self
    {
        return new self(self::LAZY);
    }
}
