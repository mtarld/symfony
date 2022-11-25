<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Type;

/**
 * @internal
 */
final class Type implements \Stringable
{
    /**
     * @param list<self|UnionType> $genericDiamondTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isGeneric = false,
        private readonly ?array $genericTypes = null,
    ) {
        if ($this->isObject() && null === $this->className) {
            throw new \InvalidArgumentException(sprintf('Missing className of "%s" type.', $this->name));
        }

        // TODO test
        if ($this->isGeneric && !$this->genericTypes) {
            throw new \InvalidArgumentException(sprintf('Missing generic types of "%s" type.', $this->name));
        }

        if ('array' === $this->name && 2 !== \count($this->genericTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid generic types of "%s" type.', $this->name));
        }
    }

    public static function createFromString(string $string): Type|UnionType
    {
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
            throw new \InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
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

                $types[] = self::createFromString($typeString);
            }

            if ($nullable) {
                $types[] = new self('null');
            }

            return new UnionType($types);
        }

        if ('null' === $string) {
            return new Type('null');
        }

        if ($isNullable = str_starts_with($string, '?')) {
            $string = substr($string, 1);
        }

        if (\count(explode('&', $string)) > 1) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        if (\in_array($string, ['int', 'string', 'float', 'bool'])) {
            return new Type($string, $isNullable);
        }

        if (class_exists($string)) {
            return new Type('object', $isNullable, $string);
        }

        $results = [];
        if (\preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $string, $results)) {
            $nestedLevel = 0;
            $genericTypes = [];
            $currentGenericType = '';

            foreach (str_split(str_replace(' ', '', $results['diamond'])) as $char) {
                if (',' === $char && 0 === $nestedLevel) {
                    $genericTypes[] = $currentGenericType;
                    $currentGenericType = '';

                    continue;
                }

                if ('<' === $char) {
                    ++$nestedLevel;
                }

                if ('>' === $char) {
                    --$nestedLevel;
                }

                $currentGenericType .= $char;
            }

            $genericTypes[] = $currentGenericType;

            if (0 !== $nestedLevel) {
                throw new \InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
            }

            if ('array' === $results['type'] && 1 === \count($genericTypes)) {
                array_unshift($genericTypes, 'int');
            }

            $type = $results['type'];
            $className = null;

            if (class_exists($type)) {
                $className = $type;
                $type = 'object';
            }

            return new Type(
                name: $type,
                isNullable: $isNullable,
                isGeneric: true,
                className: $className,
                genericTypes: array_map(fn (string $t): self|UnionType => self::createFromString($t), $genericTypes),
            );
        }

        if (class_exists($string)) {
            return new Type('object', $isNullable, $string);
        }

        throw new \InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function className(): string
    {
        if (!$this->isObject()) {
            throw new \RuntimeException(sprintf('Cannot get class on "%s" type as it\'s not an object.', $this->name));
        }

        return $this->className;
    }

    /**
     * @return list<self|UnionType>
     */
    public function genericTypes(): array
    {
        return $this->genericTypes;
    }

    public function isScalar(): bool
    {
        return in_array($this->name, ['int', 'float', 'string', 'bool'], true);
    }

    public function isNull(): bool
    {
        return 'null' === $this->name;
    }

    public function isObject(): bool
    {
        return 'object' === $this->name;
    }

    public function isGeneric(): bool
    {
        return $this->isGeneric;
    }

    public function isCollection(): bool
    {
        return 'array' === $this->name;
    }

    public function isList(): bool
    {
        return $this->isCollection() && 'int' === $this->collectionKeyType()->name();
    }

    public function isDict(): bool
    {
        return $this->isCollection() && !$this->isList();
    }

    public function collectionKeyType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericTypes[0];
    }

    public function collectionValueType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericTypes[1];
    }

    public function __toString(): string
    {
        if ($this->isNull()) {
            return 'null';
        }

        $nullablePrefix = $this->isNullable() ? '?' : '';

        $name = $this->name();
        if ($this->isObject()) {
            $name = $this->className();
        }

        if ($this->isGeneric()) {
            $name .= sprintf('<%s>', implode(', ', (string) $this->genericTypes));
        }

        return $nullablePrefix.$name;
    }

    public function validator(string $accessor): string
    {
        if ('null' === $this->name()) {
            return sprintf('null === %s', $accessor);
        }

        if ($this->isScalar()) {
            return sprintf('is_%s(%s)', $this->name(), $accessor);
        }

        if ($this->isList()) {
            return sprintf('is_array(%s) && array_is_list(%1$s)', $accessor);
        }

        if ($this->isDict()) {
            return sprintf('is_array(%s) && !array_is_list(%1$s)', $accessor);
        }

        if ($this->isObject()) {
            return sprintf('%s instanceof %s', $accessor, $this->className());
        }

        throw new \LogicException(sprintf('Cannot find validator for "%s".', (string) $this));
    }
}
