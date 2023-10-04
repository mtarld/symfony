<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Type;

use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class EnumType extends ObjectType
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        if (!is_subclass_of($className, \UnitEnum::class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid enum.', $className));
        }

        parent::__construct($className);
    }
}
