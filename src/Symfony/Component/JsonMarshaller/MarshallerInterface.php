<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller;

use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * Marshals $data into a specific format according to a $config to a string or into an $output stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-type MarshalConfig array{
 *   type?: Type,
 *   max_depth?: int,
 *   date_time_format?: string,
 * }
 */
interface MarshallerInterface
{
    /**
     * @param resource|null $output
     * @param MarshalConfig $config
     */
    public function marshal(mixed $data, array $config = [], mixed $output = null): string|null;
}
