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
 * Represents a collection in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class CollectionNode implements DataModelNodeInterface
{
    public bool $transformed;

    public function __construct(
        public PhpNodeInterface $accessor,
        public Type $type,
        public DataModelNodeInterface $item,
    ) {
        $this->transformed = $item->isTransformed();
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function isTransformed(): bool
    {
        return $this->transformed;
    }
}
