<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class UnionTypeChecker
{
    /**
     * @param list<Type>
     */
    public static function isHomogenousKind(array $types): bool
    {
        return self::checkUnionType($types, fn (Type $t): bool => $t->isScalar())
            && self::checkUnionType($types, fn (Type $t): bool => $t->isObject())
            && self::checkUnionType($types, fn (Type $t): bool => $t->isDict())
            && self::checkUnionType($types, fn (Type $t): bool => $t->isList())
        ;
    }

    /**
     * @param list<Type>
     */
    public static function isHomogenousObject(array $types): bool
    {
        return self::checkUnionType($types, fn (Type $t): bool => $t->isObject());
    }

    /**
     * @param list<Type>
     */
    public static function isHomogenousCollection(array $types): bool
    {
        return self::checkUnionType($types, fn (Type $t): bool => $t->isDict());
    }

    /**
     * @param list<Type>
     */
    private static function checkUnionType(array $types, callable $mapper): bool
    {
        return \count(\array_unique(\array_map($mapper, $types))) === 1;
    }
}
