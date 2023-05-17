<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Deserialize;

use Symfony\Component\SerDes\Exception\InvalidResourceException;
use Symfony\Component\SerDes\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface DecoderInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @throws RuntimeException
     * @throws InvalidResourceException
     */
    public function decode(mixed $resource, int $offset, int $length, array $context): mixed;
}
