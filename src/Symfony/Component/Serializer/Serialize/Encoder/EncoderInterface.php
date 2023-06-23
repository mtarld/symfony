<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Encoder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface EncoderInterface
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public static function encode(mixed $resource, mixed $normalized, array $context): void;
}

