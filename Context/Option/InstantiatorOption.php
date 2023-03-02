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
final class InstantiatorOption
{
    public const EAGER = 'eager';
    public const LAZY = 'lazy';

    /**
     * @param string|array{0: string, 1: string} $instantiator
     */
    public function __construct(
        public readonly array|string $instantiator,
    ) {
        if (\is_callable($instantiator)) {
        }

        if (!\is_callable($instantiator) && !\in_array($instantiator, [self::EAGER, self::LAZY])) {
            throw new InvalidArgumentException('TODO');
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
