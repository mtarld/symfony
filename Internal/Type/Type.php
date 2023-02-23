<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Type;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\UnaryNode;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Type implements \Stringable
{
    private readonly string $stringValue;

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
            throw new InvalidArgumentException('Missing className of "object" type.');
        }

        if ($this->isGeneric && !$this->genericParameterTypes) {
            throw new InvalidArgumentException(sprintf('Missing generic parameter types of "%s" type.', $this->name));
        }

        if ($this->isCollection() && 2 !== \count($this->genericParameterTypes)) {
            throw new InvalidArgumentException(sprintf('Invalid generic parameter types of "%s" type.', $this->name));
        }

        $this->stringValue = $this->computeStringValue();
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
            throw new LogicException(sprintf('Cannot get class on "%s" type as it\'s not an object.', $this->name));
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
        return \in_array($this->name, ['int', 'float', 'string', 'bool'], true);
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

    public function collectionKeyType(): self|UnionType
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[0];
    }

    public function collectionValueType(): self|UnionType
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', $this->name));
        }

        return $this->genericParameterTypes[1];
    }

    private function computeStringValue(): string
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

    public function __toString(): string
    {
        return $this->stringValue;
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

        throw new LogicException(sprintf('Cannot find validator for "%s".', (string) $this));
    }
}
