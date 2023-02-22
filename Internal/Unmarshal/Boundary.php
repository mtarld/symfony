<?php

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Boundary
{
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
    ) {
    }
}
