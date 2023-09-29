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
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface;

/**
 * Unmarshals an $input stream or string into a given $type according to a $config.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-type UnmarshalConfig array{
 *   lazy_read?: bool,
 *   instantiator?: InstantiatorInterface,
 *   date_time_format?: string,
 * }
 */
interface UnmarshallerInterface
{
    /**
     * @param resource|string $input
     * @param UnmarshalConfig $config
     */
    public function unmarshal(mixed $input, Type $type, array $config = []): mixed;
}
