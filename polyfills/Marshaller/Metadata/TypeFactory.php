<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

/**
 * @internal
 */
final class TypeFactory
{
    public static function createFromString(string $string): Type
    {
        if ('null' === $string) {
            return new Type('null');
        }

        if ($isNullable = '?' === $string[0]) {
            $string = substr($string, 1);
        }

        if (\count(explode('|', $string)) > 1) {
            throw new \LogicException('Not implemented yet (union/intersection).');
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
                isCollection: true,
                collectionKeyType: self::createFromString($keyType),
                collectionValueType: self::createFromString($valueType),
            );
        }

        if (class_exists($string)) {
            return new Type('object', $isNullable, $string);
        }

        throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $string));
    }

    public static function createFromReflection(\ReflectionType $reflection, \ReflectionClass $declaringClass): Type
    {
        if (!$reflection instanceof \ReflectionNamedType) {
            throw new \LogicException('Not implemented yet (union/intersection).');
        }

        $phpTypeOrClass = $reflection->getName();

        if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
            throw new \InvalidArgumentException(sprintf('Unhandled "%s" type', $reflection));
        }

        if ('array' === $phpTypeOrClass) {
            throw new \RuntimeException('todo array');
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
