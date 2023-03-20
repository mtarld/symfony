<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\DataModel\Encode;

use Symfony\Component\Encoder\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Represents an object in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param array<string, DataModelNodeInterface> $properties
     */
    public function __construct(
        public DataAccessorInterface $accessor,
        public Type $type,
        public array $properties,
        public bool $transformed,
    ) {
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }

    public function isTransformed(): bool
    {
        return $this->transformed;
    }
}
