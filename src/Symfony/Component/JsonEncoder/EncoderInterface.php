<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder;

/**
 * Encodes $data into a specific format according to a $config.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 *
 * @template T of array<string, mixed>
 */
interface EncoderInterface
{
    /**
     * @param T $config
     *
     * @return \Traversable<string>&\Stringable
     */
    public function encode(mixed $data, array $config = []): \Traversable&\Stringable;
}
