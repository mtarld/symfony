<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel\Encode;

use Symfony\Component\JsonEncoder\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Represents an object in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param ObjectType|UnionType<ObjectType|BuiltinType<TypeIdentifier::NULL>> $type
     * @param array<string, DataModelNodeInterface>                              $properties
     */
    public function __construct(
        public DataAccessorInterface $accessor,
        public ObjectType|UnionType $type,
        public array $properties,
        public bool $transformed,
    ) {
    }

    /**
     * @return ObjectType|UnionType<ObjectType|BuiltinType<TypeIdentifier::NULL>>
     */
    public function getType(): ObjectType|UnionType
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
