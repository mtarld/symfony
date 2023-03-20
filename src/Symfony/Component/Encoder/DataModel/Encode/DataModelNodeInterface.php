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
 * Represents a node in the encoding data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface DataModelNodeInterface
{
    public function getType(): Type;

    public function getAccessor(): DataAccessorInterface;

    /**
     * Whether the data will be transformed during the encoding process.
     */
    public function isTransformed(): bool;
}
