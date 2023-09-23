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

use Symfony\Component\Encoder\Stream\StreamInterface;

/**
 * Encodes $data into a specific format according to a $config to an $output stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-import-type EncodeConfig from EncoderInterface
 */
interface StreamingEncoderInterface
{
    /**
     * @param EncodeConfig $config
     */
    public function encode(mixed $data, StreamInterface $output, array $config = []): void;
}
