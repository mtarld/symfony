<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TypeGenericsHelper
{
    /**
     * @param array<string, Type> $genericTypes
     */
    public function replaceGenericTypes(Type $type, array $genericTypes): Type
    {
        $unionTypes = array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->unionTypes());
        $genericParameterTypes = array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->genericParameterTypes());

        $type = new Type(
            name: $type->name(),
            isNullable: $type->isNullable(),
            className: $type->hasClass() ? $type->className() : null,
            isGeneric: $type->isGeneric(),
            genericParameterTypes: $genericParameterTypes,
            unionTypes: $unionTypes,
        );

        return $genericTypes[(string) $type] ?? $type;
    }
}
