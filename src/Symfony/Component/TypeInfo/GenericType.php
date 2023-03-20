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

use Symfony\Component\TypeInfo\Exception\LogicException;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final class GenericType extends Type
{
    /**
     * @var list<Type>
     */
    private array $genericTypes;

    public function __construct(
        private Type $mainType,
        Type ...$genericTypes,
    ) {
        $parameterTypesStringRepresentation = '';
        $glue = '';
        foreach ($genericTypes as $t) {
            $parameterTypesStringRepresentation .= $glue.((string) $t);
            $glue = ',';
        }

        $this->stringRepresentation = ((string) $mainType).'<'.$parameterTypesStringRepresentation.'>';
        $this->genericTypes = $genericTypes;
    }

    public function getMainType(): Type
    {
        return $this->mainType;
    }

    /**
     * @return list<Type>
     */
    public function getGenericTypes(): array
    {
        return $this->genericTypes;
    }

    public function getBuiltinType(): string
    {
        return $this->mainType->getBuiltinType();
    }

    public function isNullable(): bool
    {
        return $this->mainType->isNullable();
    }

    public function isScalar(): bool
    {
        return $this->mainType->isScalar();
    }

    public function isObject(): bool
    {
        return $this->mainType->isObject();
    }

    public function getClassName(): string
    {
        return $this->mainType->getClassName();
    }

    public function isEnum(): bool
    {
        return $this->mainType->isEnum();
    }

    public function isBackedEnum(): bool
    {
        return $this->mainType->isBackedEnum();
    }

    public function getEnumBackingType(): Type
    {
        return $this->mainType->getEnumBackingType();
    }

    public function isCollection(): bool
    {
        return $this->mainType->isCollection();
    }

    /**
     * @throws LogicException
     */
    public function getCollectionKeyType(): Type
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection key type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return match (\count($this->genericTypes)) {
            2 => $this->genericTypes[0],
            1 => new Type(builtinType: self::BUILTIN_TYPE_INT),
            default => parent::getCollectionKeyType(),
        };
    }

    /**
     * @throws LogicException
     */
    public function getCollectionValueType(): Type
    {
        if (!$this->isCollection()) {
            throw new LogicException(sprintf('Cannot get collection value type on "%s" type as it\'s not a collection.', (string) $this));
        }

        return match (\count($this->genericTypes)) {
            2 => $this->genericTypes[1],
            1 => $this->genericTypes[0],
            default => parent::getCollectionValueType(),
        };
    }

    public function __toString(): string
    {
        return $this->stringRepresentation;
    }
}
