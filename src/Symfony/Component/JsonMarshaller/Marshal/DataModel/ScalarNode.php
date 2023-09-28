<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\DataModel;

use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * Represents a scalar in the data model graph representation.
 *
 * Scalars are the leaves of the data model tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class ScalarNode implements DataModelNodeInterface
{
    public function __construct(
        public PhpNodeInterface $accessor,
        public Type $type,
    ) {
    }

    public function type(): Type
    {
        return $this->type;
    }
}
