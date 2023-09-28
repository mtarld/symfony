<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Attribute;

/**
 * Defines the marshalled property name.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class MarshalledName
{
    public function __construct(
        public string $name,
    ) {
    }
}
