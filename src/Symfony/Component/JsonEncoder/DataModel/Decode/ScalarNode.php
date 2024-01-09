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

use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Represents a scalar in the data model graph representation.
 *
 * Scalars are the leaves of the data model tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class ScalarNode implements DataModelNodeInterface
{
    /**
     * @param BuiltinType|EnumType|UnionType<BuiltinType|EnumType> $type
     */
    public function __construct(
        public BuiltinType|EnumType|UnionType $type,
    ) {
    }

    public function getIdentifier(): string
    {
        return (string) $this->type;
    }

    /**
     * @return BuiltinType|EnumType|UnionType<BuiltinType|EnumType>
     */
    public function getType(): BuiltinType|EnumType|UnionType
    {
        return $this->type;
    }

    public function isTransformed(): bool
    {
        $nonNullableType = $this->type;
        try {
            $nonNullableType = $nonNullableType->asNonNullable();
        } catch (LogicException) {
        }

        return $nonNullableType instanceof BackedEnumType;
    }
}
