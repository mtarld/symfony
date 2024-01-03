<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel\Decode;

use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Represents a collection in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class CollectionNode implements DataModelNodeInterface
{
    /**
     * @param CollectionType|UnionType<CollectionType|BuiltinType<TypeIdentifier::NULL>> $type
     */
    public function __construct(
        public CollectionType|UnionType $type,
        public DataModelNodeInterface $item,
    ) {
    }

    public function getIdentifier(): string
    {
        return (string) $this->type;
    }

    /**
     * @return CollectionType|UnionType<CollectionType|BuiltinType<TypeIdentifier::NULL>>
     */
    public function getType(): CollectionType|UnionType
    {
        return $this->type;
    }

    public function isTransformed(): bool
    {
        return $this->item->isTransformed();
    }
}
