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

use Symfony\Component\SerDes\Exception\InvalidTypeException;
use Symfony\Component\SerDes\Exception\UnsupportedTypeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract class TypeFactory
{
    /**
     * @var array<string, Type>
     */
    private static array $cache = [];

    private function __construct()
    {
    }

    public static function createFromString(string $string): Type
    {
        if (isset(self::$cache[$cacheKey = $string])) {
            return self::$cache[$cacheKey];
        }

        $currentTypeString = '';
        $typeStrings = [];
        $nestedLevel = 0;

        foreach (str_split(str_replace(' ', '', $string)) as $char) {
            if ('<' === $char) {
                ++$nestedLevel;
            }

            if ('>' === $char) {
                --$nestedLevel;
            }

            if ('|' === $char && 0 === $nestedLevel) {
                $typeStrings[] = $currentTypeString;
                $currentTypeString = '';

                continue;
            }

            $currentTypeString .= $char;
        }

        $typeStrings[] = $currentTypeString;

        if (0 !== $nestedLevel) {
            throw new InvalidTypeException($string);
        }

        if (\count($typeStrings) > 1) {
            $nullable = false;

            $types = [];

            foreach ($typeStrings as $typeString) {
                if (str_starts_with($typeString, '?')) {
                    $nullable = true;
                    $typeString = substr($typeString, 1);
                }

                if ('null' === $typeString) {
                    $nullable = true;

                    continue;
                }

                /** @var Type $type */
                $type = self::createFromString($typeString);
                $types[] = $type;
            }

            if ($nullable) {
                $types[] = new Type('null');
            }

            return self::$cache[$cacheKey] = new Type($string, unionTypes: $types);
        }

        if ('null' === $string) {
            return self::$cache[$cacheKey] = new Type('null');
        }

        if ($isNullable = str_starts_with($string, '?')) {
            $string = substr($string, 1);
        }

        if (\count(explode('&', $string)) > 1) {
            throw new UnsupportedTypeException($string);
        }

        if (\in_array($string, ['int', 'string', 'float', 'bool'])) {
            return self::$cache[$cacheKey] = new Type($string, $isNullable);
        }

        if (is_subclass_of($string, \UnitEnum::class)) {
            if (is_subclass_of($string, \BackedEnum::class)) {
                return self::$cache[$cacheKey] = new Type('enum', $isNullable, $string);
            }

            throw new InvalidTypeException($string);
        }

        if (class_exists($string) || interface_exists($string)) {
            return self::$cache[$cacheKey] = new Type('object', $isNullable, $string);
        }

        $results = [];
        if (preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $string, $results)) {
            $genericType = $results['type'];
            $genericParameters = [];
            $currentGenericParameter = '';
            $nestedLevel = 0;

            foreach (str_split(str_replace(' ', '', $results['diamond'])) as $char) {
                if (',' === $char && 0 === $nestedLevel) {
                    $genericParameters[] = $currentGenericParameter;
                    $currentGenericParameter = '';

                    continue;
                }

                if ('<' === $char) {
                    ++$nestedLevel;
                }

                if ('>' === $char) {
                    --$nestedLevel;
                }

                $currentGenericParameter .= $char;
            }

            $genericParameters[] = $currentGenericParameter;

            if (0 !== $nestedLevel) {
                throw new InvalidTypeException($string);
            }

            if (\in_array($genericType, ['array', 'iterable'], true) && 1 === \count($genericParameters)) {
                array_unshift($genericParameters, 'int');
            }

            $type = $genericType;
            $className = null;

            if (class_exists($genericType)) {
                $type = 'object';
                $className = $genericType;
            }

            return self::$cache[$cacheKey] = new Type(
                name: $type,
                isNullable: $isNullable,
                className: $className,
                genericParameterTypes: array_map(fn (string $t): Type => self::createFromString($t), $genericParameters),
            );
        }

        return self::$cache[$cacheKey] = new Type($string, $isNullable);
    }
}
