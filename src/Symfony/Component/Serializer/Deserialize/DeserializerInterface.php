<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize;

use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface DeserializerInterface
{
    /**
     * @throws UnsupportedException
     */
    public function deserialize(StreamInterface|string $input, Type|string $type, string $format, Configuration $configuration = null): mixed;
}
