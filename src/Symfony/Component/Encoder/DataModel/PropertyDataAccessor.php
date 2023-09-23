<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\DataModel;

/**
 * Defines the way to access data using an object property.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class PropertyDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public DataAccessorInterface $objectAccessor,
        public string $propertyName,
    ) {
    }
}
