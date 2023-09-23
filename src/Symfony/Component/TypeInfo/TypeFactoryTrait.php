<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
trait TypeFactoryTrait
{
    public static function builtin(string $type, bool $nullable = false): Type
    {
        if (!\in_array($type, Type::BUILTIN_TYPES, true)) {
            throw new InvalidArgumentException('TODO');
        }

        $type = new Type($type);
        if ($nullable) {
            return new UnionType(self::null(), $type);
        }

        return $type;
    }

    public static function int(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_INT, $nullable);
    }

    public static function float(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_FLOAT, $nullable);
    }

    public static function string(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_STRING, $nullable);
    }

    public static function bool(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_BOOL, $nullable);
    }

    public static function resource(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_RESOURCE, $nullable);
    }

    public static function false(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_FALSE, $nullable);
    }

    public static function true(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_TRUE, $nullable);
    }

    public static function callable(bool $nullable = false): Type
    {
        return self::builtin(Type::BUILTIN_TYPE_CALLABLE, $nullable);
    }

    public static function mixed(): Type
    {
        return new Type(Type::BUILTIN_TYPE_MIXED);
    }

    public static function null(): Type
    {
        return new Type(Type::BUILTIN_TYPE_NULL);
    }

    public static function array(Type $value = null, Type $key = null, bool $nullable = false): Type
    {
        $mainType = self::builtin(Type::BUILTIN_TYPE_ARRAY, nullable: $nullable);

        return new GenericType(
            $mainType,
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function list(Type $value = null, bool $nullable = false): Type
    {
        return self::array($value, self::int(), $nullable);
    }

    public static function dict(Type $value = null, bool $nullable = false): Type
    {
        return self::array($value, self::string(), $nullable);
    }

    public static function iterable(Type $value = null, Type $key = null, bool $nullable = false): Type
    {
        $mainType = self::builtin(Type::BUILTIN_TYPE_ITERABLE, nullable: $nullable);

        return new GenericType(
            $mainType,
            $key ?? self::union(self::int(), self::string()),
            $value ?? self::mixed(),
        );
    }

    public static function iterableList(Type $value = null, bool $nullable = false): Type
    {
        return self::iterable($value, self::int(), $nullable);
    }

    public static function iterableDict(Type $value = null, bool $nullable = false): Type
    {
        return self::iterable($value, self::string(), $nullable);
    }

    /**
     * @param class-string|null $className
     */
    public static function object(string $className = null, bool $nullable = false): self
    {
        $type = new Type(builtinType: self::BUILTIN_TYPE_OBJECT, className: $className);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    /**
     * @param class-string $enumClassName
     */
    public static function enum(string $enumClassName, Type $backingType = null, bool $nullable = false): self
    {
        $type = new Type(className: $enumClassName, enumBackingType: $backingType);
        if ($nullable) {
            return new UnionType(new Type(self::BUILTIN_TYPE_NULL), $type);
        }

        return $type;
    }

    public static function generic(Type $mainType, self ...$parametersType): self
    {
        return new GenericType($mainType, ...$parametersType);
    }

    public static function union(self ...$types): self
    {
        return new UnionType(...$types);
    }

    public static function intersection(self ...$types): self
    {
        return new IntersectionType(...$types);
    }
}
