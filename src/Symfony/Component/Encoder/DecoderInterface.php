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

use Symfony\Component\TypeInfo\Type;

/**
 * Decodes an $input string into a given $type according to a $config.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-type DecodeConfig array{
 *   date_time_format?: string,
 * }
 */
interface DecoderInterface
{
    /**
     * @param DecodeConfig $config
     */
    public function decode(string $input, Type $type, array $config = []): mixed;
}
