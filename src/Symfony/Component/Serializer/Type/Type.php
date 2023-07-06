<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Type;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Type implements \Stringable
{
    /**
     * @var array<string, Type>
     */
    private static array $cache = [];

    private readonly string $stringValue;

    /**
     * @param class-string|null $className
     * @param list<self>        $genericParameterTypes
     * @param list<self>        $unionTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly array $genericParameterTypes = [],
        private readonly array $unionTypes = [],
    ) {
        if (1 === \count($this->unionTypes)) {
            throw new InvalidArgumentException(sprintf('Cannot define only one union type for "%s" type.', $this->name));
        }

        $this->stringValue = $this->computeStringValue();
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        if (!$this->isObject() && !$this->isEnum()) {
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an object nor an enum.', $this->name));
        }

        if (null === $this->className) {
            throw new LogicException(sprintf('No class has been defined for "%s".', $this->name));
        }

        return $this->className;
    }

    /**
     * @return list<self>
     */
    public function genericParameterTypes(): array
    {
        return $this->genericParameterTypes;
    }

    /**
     * @return list<self>
     */
    public function unionTypes(): array
    {
        return $this->unionTypes;
    }

    public function isScalar(): bool
    {
        if ($this->isUnion()) {
            return array_reduce($this->unionTypes, fn (bool $c, self $t): bool => $c && $t->isScalar(), true);
        }

        return \in_array($this->name, ['int', 'float', 'string', 'bool', 'null'], true);
    }

    public function isNull(): bool
    {
        return 'null' === $this->name;
    }

    public function isNullable(): bool
    {
        if ($this->isUnion()) {
            foreach ($this->unionTypes as $type) {
                if ($type->isNull()) {
                    return true;
                }
            }

            return false;
        }

        return $this->isNullable;
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function hasClass(): bool
    {
        return null !== $this->className;
    }

    public function isEnum(): bool
    {
        return 'enum' === $this->name;
    }

    public function isGeneric(): bool
    {
        return [] !== $this->genericParameterTypes;
    }

    public function isUnion(): bool
    {
        return [] !== $this->unionTypes;
    }

    public function isCollection(): bool
    {
        if ($this->isUnion()) {
            return array_reduce($this->unionTypes, fn (bool $c, self $t): bool => $c && $t->isCollection(), true);
        }

        return \in_array($this->name, ['array', 'iterable'], true);
    }

    public function isIterable(): bool
    {
        return 'iterable' === $this->name;
    }

    public function isList(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'int' === $collectionKeyType->name();
    }

    public function isDict(): bool
    {
        if (!$this->isCollection()) {
            return false;
        }

        $collectionKeyType = $this->collectionKeyType();
        if (!$collectionKeyType instanceof self) {
            return false;
        }

        return 'string' === $collectionKeyType->name();
    }

    public function collectionKeyType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[0] ?? new self('mixed');
    }

    public function collectionValueType(): self
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[1] ?? new self('mixed');
    }

    public function __toString(): string
    {
        return $this->stringValue;
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
            throw new InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
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
            throw new UnsupportedException(sprintf('"%s" type is not supported.', $string));
        }

        if (\in_array($string, ['int', 'string', 'float', 'bool'])) {
            return self::$cache[$cacheKey] = new Type($string, $isNullable);
        }

        if (is_subclass_of($string, \UnitEnum::class)) {
            if (is_subclass_of($string, \BackedEnum::class)) {
                return self::$cache[$cacheKey] = new Type('enum', $isNullable, $string);
            }

            throw self::invalidTypeException($string);
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
                throw self::invalidTypeException($string);
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

    private function computeStringValue(): string
    {
        if ($this->isUnion()) {
            return implode('|', array_map(fn (Type $t): string => (string) $t, $this->unionTypes));
        }

        if ($this->isNull()) {
            return 'null';
        }

        $name = $this->hasClass() ? $this->className() : $this->name();

        if ($this->isGeneric()) {
            $name .= sprintf('<%s>', implode(', ', $this->genericParameterTypes));
        }

        return ($this->isNullable() ? '?' : '').$name;
    }

    private static function invalidTypeException(string $type): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf('Invalid "%s" type.', $type));
    }
}
