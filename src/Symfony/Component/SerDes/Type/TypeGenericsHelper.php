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
     * @template T of Type|UnionType
     *
     * @param T                             $type
     * @param array<string, Type|UnionType> $genericTypes
     *
     * @return T
     */
    public function replaceGenericTypes(Type|UnionType $type, array $genericTypes): Type|UnionType
    {
        if ($type instanceof UnionType) {
            return new UnionType(array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->types));
        }

        if ([] !== $type->genericParameterTypes()) {
            $genericParameterTypes = array_map(fn (Type|UnionType $t): Type|UnionType => $this->replaceGenericTypes($t, $genericTypes), $type->genericParameterTypes());

            return new Type(
                name: $type->name(),
                isNullable: $type->isNullable(),
                className: $type->hasClass() ? $type->className() : null,
                isGeneric: true,
                genericParameterTypes: $genericParameterTypes,
            );
        }

        /** @var T|null $genericType */
        $genericType = $genericTypes[(string) $type] ?? null;
        if (null !== $genericType) {
            return $genericType;
        }

        return $type;
    }
}
