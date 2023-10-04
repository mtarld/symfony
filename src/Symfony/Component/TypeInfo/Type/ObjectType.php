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

use Symfony\Component\TypeInfo\BuiltinType;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class ObjectType extends Type
{
    /**
     * @param class-string $className
     */
    public function __construct(
        private readonly string $className,
    ) {
    }

    public function getBuiltinType(): BuiltinType
    {
        return BuiltinType::OBJECT;
    }

    /**
     * @return class-string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return class-string
     */
    public function __toString(): string
    {
        return $this->className;
    }
}
