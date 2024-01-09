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
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\EnumType;

/**
 * Represents a scalar in the data model graph representation.
 *
 * Scalars are the leaves of the data model tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class ScalarNode implements DataModelNodeInterface
{
    public function __construct(
        public DataAccessorInterface $accessor,
        public BuiltinType|EnumType $type,
    ) {
    }

    public function getType(): BuiltinType|EnumType
    {
        return $this->type;
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }

    public function isTransformed(): bool
    {
        return $this->type instanceof BackedEnumType;
    }
}
