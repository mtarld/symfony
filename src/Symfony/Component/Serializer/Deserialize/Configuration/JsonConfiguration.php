<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Configuration;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class JsonConfiguration
{
    protected int $flags = 0;

    public function flags(): int
    {
        return $this->flags;
    }

    public function withFlags(int $groups): static
    {
        $clone = clone $this;
        $clone->flags = $groups;

        return $clone;
    }
}
