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

use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * Represents a node in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface DataModelNodeInterface
{
    public function type(): Type;

    /**
     * Whether the data will be transformed during the marshal process.
     */
    public function isTransformed(): bool;
}
