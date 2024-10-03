<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel;

/**
 * Defines the way to access data using an object property.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class PropertyDataAccessor implements DataAccessorInterface
{
    public function __construct(
        private DataAccessorInterface $objectAccessor,
        private string $propertyName,
    ) {
    }

    public function getObjectAccessor(): DataAccessorInterface
    {
        return $this->objectAccessor;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
