<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;


/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class PropertyMetadata
{
    /**
     * @param callable(callable(Type): mixed): mixed $valueProvider
     */
    public function __construct(
        public string $name,
        public mixed $valueProvider
    ) {
    }
}
