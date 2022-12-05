<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Type;

use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\UnaryNode;

/**
 * @internal
 */
final class Type implements \Stringable
{
    /**
     * @param class-string|null    $className
     * @param list<self|UnionType> $genericParameterTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $isNullable = false,
        private readonly ?string $className = null,
        private readonly bool $isGeneric = false,
        private readonly array $genericParameterTypes = [],
    ) {
        if ($this->isObject() && null === $this->className) {
            throw new \InvalidArgumentException('Missing className of "object" type.');
        }

        if ($this->isGeneric && !$this->genericParameterTypes) {
            throw new \InvalidArgumentException(sprintf('Missing generic parameter types of "%s" type.', $this->name));
        }

        if ($this->isCollection() && 2 !== \count($this->genericParameterTypes)) {
            throw new \InvalidArgumentException(sprintf('Invalid generic parameter types of "%s" type.', $this->name));
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

                /** @var self $type */
                $type = self::createFromString($typeString);
                $types[] = $type;
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

        if (class_exists($string) || interface_exists($string)) {
            return new Type('object', $isNullable, $string);
        }

        $results = [];
        if (\preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $string, $results)) {
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
                throw new \InvalidArgumentException(sprintf('Invalid "%s" type.', $string));
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

            return new Type(
                name: $type,
                isNullable: $isNullable,
                isGeneric: true,
                className: $className,
                genericParameterTypes: array_map(fn (string $t): self|UnionType => self::createFromString($t), $genericParameters),
            );
        }

        return new Type($string, $isNullable);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        if (!$this->isObject()) {
            throw new \RuntimeException(sprintf('Cannot get class on "%s" type as it\'s not an object.', $this->name));
        }

        /** @var class-string $className */
        $className = $this->className;

        return $className;
    }

    /**
     * @return list<self|UnionType>
     */
    public function genericParameterTypes(): array
    {
        return $this->genericParameterTypes;
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
        return $this->isCollection() && !$this->isList();
    }

    public function collectionKeyType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[0];
    }

    public function collectionValueType(): Type|UnionType
    {
        if (!$this->isCollection()) {
            throw new \RuntimeException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[1];
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
            $name .= sprintf('<%s>', implode(', ', $this->genericParameterTypes));
        }

        return $nullablePrefix.$name;
    }

    public function validator(NodeInterface $accessor): NodeInterface
    {
        if ('null' === $this->name()) {
            return new BinaryNode('===', new ScalarNode(null), $accessor);
        }

        if ($this->isScalar()) {
            return new FunctionNode(sprintf('\is_%s', $this->name()), [$accessor]);
        }

        if ($this->isList()) {
            return new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new FunctionNode('\array_is_list', [$accessor]));
        }

        if ($this->isDict()) {
            return new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new UnaryNode('!', new FunctionNode('\array_is_list', [$accessor])));
        }

        if ($this->isObject()) {
            return new BinaryNode('instanceof', $accessor, new ScalarNode($this->className()));
        }

        throw new \LogicException(sprintf('Cannot find validator for "%s".', (string) $this));
    }
}
