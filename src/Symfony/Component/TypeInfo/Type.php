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

use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class Type implements \Stringable
{
    use TypeFactoryTrait;

    public function getBaseType(): BuiltinType|ObjectType
    {
        if ($this instanceof UnionType || $this instanceof IntersectionType) {
            throw new LogicException(sprintf('Cannot get base type on "%s" compound type.', (string) $this));
        }

        $baseType = $this;

        if ($baseType instanceof CollectionType) {
            $baseType = $baseType->getType();
        }

        if ($baseType instanceof GenericType) {
            $baseType = $baseType->getType();
        }

        return $baseType;
    }

    /**
     * @param callable(Type): bool $callable
     */
    public function is(callable $callable): bool
    {
        if ($this instanceof UnionType) {
            return $this->atLeastOneTypeIs($callable);
        }

        if ($this instanceof IntersectionType) {
            return $this->everyTypeIs($callable);
        }

        return $callable($this);
    }

    public function isBuiltinType(BuiltinTypeEnum $builtinType): bool
    {
        return $this->is(static function (self $t) use ($builtinType): bool {
            try {
                $b = $t->getBaseType();
            } catch (\LogicException) {
                return false;
            }

            return $builtinType === $b->getBuiltinType();
        });
    }

    public function isNullable(): bool
    {
        return $this->isBuiltinType(BuiltinTypeEnum::NULL) || $this->isBuiltinType(BuiltinTypeEnum::MIXED);
    }
}
