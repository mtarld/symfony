<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Attribute;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    public readonly ?\Closure $marshalFormatter;
    public readonly ?\Closure $unmarshalFormatter;

    /**
     * @param string|array{0: string, 1: string} $marshal
     * @param string|array{0: string, 1: string} $unmarshal
     */
    public function __construct(
        string|array $marshal = null,
        string|array $unmarshal = null,
    ) {
        if (null !== $marshal) {
            if (!\is_callable($marshal)) {
                throw new InvalidArgumentException(sprintf('Parameter "$marshal" of attribute "%s" must be a valid callable.', self::class));
            }
        }

        if (null !== $unmarshal) {
            if (!\is_callable($unmarshal)) {
                throw new InvalidArgumentException(sprintf('Parameter "$unmarshal" of attribute "%s" must be a valid callable.', self::class));
            }
        }

        $this->marshalFormatter = null !== $marshal ? \Closure::fromCallable($marshal) : null;
        $this->unmarshalFormatter = null !== $unmarshal ? \Closure::fromCallable($unmarshal) : null;
    }
}
