<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Attribute;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Name
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
