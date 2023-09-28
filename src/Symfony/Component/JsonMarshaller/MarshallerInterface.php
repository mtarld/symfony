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

/**
 * Marshals $data into a specific format according to a $config to a string or into an $output stream.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
interface MarshallerInterface
{
    // TODO
    /**
     * @param resource|null $output
     */
    public function marshal(mixed $data, array $config = [], mixed $output = null): string|null;
}
