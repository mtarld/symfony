<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Stream;

/**
 * Opens and holds a "php://memory" resource.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
final class MemoryStream extends Stream
{
    public function __construct(string $mode = 'w+b')
    {
        parent::__construct('php://memory', $mode);
    }
}
