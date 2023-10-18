<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder;

use Symfony\Component\Encoder\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Encodes $data into a specific format according to a $config.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-type EncodeConfig array{
 *   type?: Type,
 *   stream?: StreamWriterInterface,
 *   max_depth?: int,
 *   date_time_format?: string,
 * }
 */
interface EncoderInterface
{
    /**
     * @param EncodeConfig $config
     *
     * @return \Traversable<string>&\Stringable
     */
    public function encode(mixed $data, array $config = []): \Traversable&\Stringable;
}
