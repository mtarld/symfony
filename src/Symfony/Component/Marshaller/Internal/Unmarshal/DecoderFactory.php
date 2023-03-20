<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDecoder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class DecoderFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): DecoderInterface
    {
        return match ($format) {
            'json' => new JsonDecoder(),
            default => throw new UnsupportedFormatException($format),
        };
    }
}
