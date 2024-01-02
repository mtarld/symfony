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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Represents a collection in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class CollectionNode implements DataModelNodeInterface
{
    public bool $transformed;

    /**
     * @param CollectionType|UnionType<CollectionType|BuiltinType> $type
     */
    public function __construct(
        public DataAccessorInterface $accessor,
        public CollectionType|UnionType $type,
        public DataModelNodeInterface $item,
    ) {
        $this->transformed = $item->isTransformed();
    }

    /**
     * @return CollectionType|UnionType<CollectionType|BuiltinType>
     */
    public function getType(): Type|UnionType
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
