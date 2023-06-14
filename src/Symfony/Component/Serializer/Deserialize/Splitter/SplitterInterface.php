<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Splitter;

use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SplitterInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<int|string, array{0: int, 1: int}>|null
     */
    public function split(mixed $resource, Type $type, array $context): ?\Iterator;
}
