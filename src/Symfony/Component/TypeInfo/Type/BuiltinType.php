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

use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class BuiltinType extends Type
{
    public function __construct(
        private readonly BuiltinTypeEnum $builtinType,
    ) {
    }

    public function getBuiltinType(): BuiltinTypeEnum
    {
        return $this->builtinType;
    }

    public function __toString(): string
    {
        return $this->builtinType->value;
    }
}
