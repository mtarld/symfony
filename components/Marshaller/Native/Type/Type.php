<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Type;

/**
 * @internal
 */
final class Type implements \Stringable
{
    private function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly self|UnionType|null $collectionKeyType = null,
        private readonly self|UnionType|null $collectionValueType = null
    ) {
        if ($this->isObject() && null === $this->className) {
            throw new \InvalidArgumentException(sprintf('Class name of "%s" has not been set.', $this->name));
        }
    }

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

    public function isCollection(): bool
    {
        return null !== $this->collectionKeyType && null !== $this->collectionValueType;
    }

    public function isList(): bool
    {
        return $this->isCollection() && 'int' === $this->collectionKeyType?->name();
    }

    public function isDict(): bool
    {
        return $this->isCollection() && !$this->isList();
    }

    public function collectionKeyType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection key types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionKeyType;
    }

    public function collectionValueType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException('Cannot get collection value types on "%s" type as it\'s not a collection', $this->name);
        }

        return $this->collectionValueType;
    }

    public function __toString(): string
    {
        if ($this->isNull()) {
            return 'null';
        }

        $nullablePrefix = $this->isNullable() ? '?' : '';

        if ($this->isCollection()) {
            $diamond = '';
            if ($this->collectionKeyType && $this->collectionValueType) {
                $diamond = sprintf('<%s, %s>', (string) $this->collectionKeyType, (string) $this->collectionValueType);
            }

            return $nullablePrefix.'array'.$diamond;
        }

        $name = $this->name();
        if ($this->isObject()) {
            $name = $this->className();
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

        throw new \RuntimeException(sprintf('Cannot find validator for "%s"', (string) $this));
    }
}
