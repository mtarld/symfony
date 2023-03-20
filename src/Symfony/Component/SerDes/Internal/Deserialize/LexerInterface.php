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

use Symfony\Component\SerDes\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface LexerInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @return \Iterator<array{0: string, 1: int}>
     *
     * @throws RuntimeException
     */
    public function tokens(mixed $resource, int $offset, int $length, array $context): \Iterator;
}
