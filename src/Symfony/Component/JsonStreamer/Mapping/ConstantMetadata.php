<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Mapping;

/**
 * Holds stream reading/writing metadata about a given constant.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class ConstantMetadata
{
    public function __construct(
        private mixed $value,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function withValue(mixed $value): self
    {
        return new self($value);
    }
}
