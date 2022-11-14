<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

/**
 * @internal
 */
final class TypeFactory
{
    public static function createFromString(string $string): Type|UnionType
    {
        if ('null' === $string) {
            return new Type('null');
        }

        if ($isNullable = str_starts_with($string, '?')) {
            $string = substr($string, 1);
        }

        if (\count(explode('&', $string)) > 1) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if (in_array($string, ['int', 'string', 'float', 'bool'])) {
            return new Type($string, $isNullable);
        }

        $results = [];
        if (preg_match('/^array<(?P<diamond>.+)>$/', $string, $results)) {
            $nestedLevel = 0;
            $keyType = $valueType = '';
            $isReadingKey = true;

            foreach (str_split(str_replace(' ', '', $results['diamond'])) as $char) {
                if (',' === $char && 0 === $nestedLevel) {
                    $isReadingKey = false;
                    continue;
                }

                if ('<' === $char) {
                    ++$nestedLevel;
                }

                if ('>' === $char) {
                    --$nestedLevel;
                }

                if ($isReadingKey) {
                    $keyType .= $char;
                } else {
                    $valueType .= $char;
                }
            }

            if ('' === $valueType) {
                $valueType = $keyType;
                $keyType = 'int';
            }

            return new Type(
                name: 'array',
                isNullable: $isNullable,
                collectionKeyType: self::createFromString($keyType),
                collectionValueType: self::createFromString($valueType),
            );
        }

        if (class_exists($string)) {
            return new Type('object', $isNullable, $string);
        }

        if (\count($types = explode('|', $string)) > 1) {
            return new UnionType(array_map(fn (string $t): Type => self::createFromString($t), $types));
        }

        throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $string));
    }

    public static function createFromReflection(\ReflectionType $reflection, \ReflectionClass $declaringClass): Type|UnionType
    {
        if ($reflection instanceof \ReflectionIntersectionType) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if ($reflection instanceof \ReflectionUnionType) {
            return new UnionType(array_map(fn (\ReflectionNamedType $t): Type => self::createFromReflection($t, $declaringClass), $reflection->getTypes()));
        }

        $phpTypeOrClass = $reflection->getName();

        if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $phpTypeOrClass));
        }

        if ('array' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $phpTypeOrClass));
        }

        if ($reflection->isBuiltin()) {
            return new Type(name: $phpTypeOrClass, isNullable: $reflection->allowsNull());
        }

        $className = $phpTypeOrClass;

        if ($declaringClass && 'self' === strtolower($className)) {
            $className = $declaringClass->name;
        } elseif ($declaringClass && 'parent' === strtolower($className) && $parent = $declaringClass->getParentClass()) {
            $className = $parent->name;
        }

        return new Type(name: 'object', isNullable: $reflection->allowsNull(), className: $className);
    }
}
