<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Internal\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface ListSplitterInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<array{0: int, 1: int}>|null
     */
    public function split(mixed $resource, Type $type, array $context): ?\Iterator;
}
