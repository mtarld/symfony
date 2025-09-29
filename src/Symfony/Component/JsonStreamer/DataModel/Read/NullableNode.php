<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\DataModel\Read;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\NullableType;

/**
 * Represents a nullable node in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class NullableNode implements DataModelNodeInterface
{
    public function __construct(
        private DataModelNodeInterface $node,
    ) {
    }

    public function getIdentifier(): string
    {
        return (string) $this->getType();
    }

    public function getType(): NullableType
    {
        return Type::nullable($this->node->getType());
    }

    public function getNode(): DataModelNodeInterface
    {
        return $this->node;
    }
}
