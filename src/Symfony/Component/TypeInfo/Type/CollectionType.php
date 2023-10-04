<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Type;

use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class CollectionType extends Type
{
    public function __construct(
        private readonly BuiltinType|ObjectType|GenericType $type,
    ) {
    }

    public function getType(): BuiltinType|ObjectType|GenericType
    {
        return $this->type;
    }

    public function getCollectionKeyType(): Type
    {
        $defaultCollectionKeyType = self::union(self::int(), self::string());

        if ($this->type instanceof GenericType) {
            return match (\count($this->type->getGenericTypes())) {
                2 => $this->type->getGenericTypes()[0],
                1 => self::int(),
                default => $defaultCollectionKeyType,
            };
        }

        return $defaultCollectionKeyType;
    }

    public function getCollectionValueType(): Type
    {
        $defaultCollectionValueType = self::mixed();

        if ($this->type instanceof GenericType) {
            return match (\count($this->type->getGenericTypes())) {
                2 => $this->type->getGenericTypes()[1],
                1 => $this->type->getGenericTypes()[0],
                default => $defaultCollectionValueType,
            };
        }

        return $defaultCollectionValueType;
    }

    public function __toString(): string
    {
        return (string) $this->type;
    }

    /**
     * Proxies all method calls to the original type.
     *
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->type->{$method}(...$arguments);
    }
}
