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

use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\TemplateType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
trait TypeFactoryTrait
{
    public static function builtin(BuiltinTypeEnum|string $type): BuiltinType
    {
        return new BuiltinType(\is_string($type) ? BuiltinTypeEnum::from($type) : $type);
    }

    public static function int(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::INT);
    }

    public static function float(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::FLOAT);
    }

    public static function string(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::STRING);
    }

    public static function bool(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::BOOL);
    }

    public static function resource(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::RESOURCE);
    }

    public static function false(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::FALSE);
    }

    public static function true(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::TRUE);
    }

    public static function callable(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::CALLABLE);
    }

    public static function mixed(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::MIXED);
    }

    public static function null(): BuiltinType
    {
        return self::builtin(BuiltinTypeEnum::NULL);
    }

    public static function collection(Type $type, Type $value = null, Type $key = null): CollectionType
    {
        if (null !== $value || null !== $key) {
            $type = self::generic($type, $key ?? self::union(self::int(), self::string()), $value ?? self::mixed());
        }

        return new CollectionType($type);
    }

    public static function array(Type $value = null, Type $key = null): CollectionType
    {
        return self::collection(self::builtin(BuiltinTypeEnum::ARRAY), $value, $key);
    }

    public static function iterable(Type $value = null, Type $key = null): CollectionType
    {
        return self::collection(self::builtin(BuiltinTypeEnum::ITERABLE), $value, $key);
    }

    public static function list(Type $value = null): CollectionType
    {
        return self::array($value, self::int());
    }

    public static function dict(Type $value = null): CollectionType
    {
        return self::array($value, self::string());
    }

    /**
     * @param class-string|null $className
     */
    public static function object(string $className = null): BuiltinType|ObjectType
    {
        return null !== $className ? new ObjectType($className) : new BuiltinType(BuiltinTypeEnum::OBJECT);
    }

    /**
     * @param class-string $className
     */
    public static function enum(string $className, Type $backingType = null): EnumType
    {
        if (is_subclass_of($className, \BackedEnum::class)) {
            if (null === $backingType) {
                $reflectionBackingType = (new \ReflectionEnum($className))->getBackingType();
                $builtinType = BuiltinTypeEnum::INT->value === (string) $reflectionBackingType ? BuiltinTypeEnum::INT : BuiltinTypeEnum::STRING;
                $backingType = new BuiltinType($builtinType);
            }

            $type = new BackedEnumType($className, $backingType);
        } else {
            if (null !== $backingType) {
                throw new InvalidArgumentException(sprintf('Cannot set a backing type for "%s" as it is not a backed enum.', $className));
            }

            $type = new EnumType($className);
        }

        return $type;
    }

    public static function generic(Type $mainType, Type ...$parametersType): GenericType
    {
        return new GenericType($mainType, ...$parametersType);
    }

    public static function template(string $template): TemplateType
    {
        return new TemplateType($template);
    }

    /**
     * @param list<Type> $types
     *
     * @return UnionType<Type>
     */
    public static function union(Type ...$types): UnionType
    {
        $unionTypes = [];

        foreach ($types as $type) {
            if (!$type instanceof UnionType) {
                $unionTypes[] = $type;

                continue;
            }

            foreach ($type->getTypes() as $unionType) {
                $unionTypes[] = $unionType;
            }
        }

        return new UnionType(...$unionTypes);
    }

    /**
     * @param list<Type> $types
     *
     * @return IntersectionType<Type>
     */
    public static function intersection(Type ...$types): IntersectionType
    {
        $intersectionTypes = [];

        foreach ($types as $type) {
            if (!$type instanceof IntersectionType) {
                $intersectionTypes[] = $type;

                continue;
            }

            foreach ($type->getTypes() as $intersectionType) {
                $intersectionTypes[] = $intersectionType;
            }
        }

        return new IntersectionType(...$intersectionTypes);
    }

    /**
     * @template T of Type
     *
     * @param T $type
     *
     * @return UnionType<T|BuiltinType>
     */
    public static function nullable(Type $type): UnionType
    {
        if ($type instanceof UnionType) {
            return Type::union(Type::null(), ...$type->getTypes());
        }

        return Type::union($type, Type::null());
    }
}
