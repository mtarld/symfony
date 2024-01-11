<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use Symfony\Component\JsonEncoder\DataModel\DataAccessorInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\CollectionNode as DecodeCollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelNodeInterface as DecodeDataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\ObjectNode as DecodeObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\CollectionNode as EncodeCollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelNodeInterface as EncodeDataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\ObjectNode as EncodeObjectNode;
use Symfony\Component\JsonEncoder\DataModel\FunctionDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\PhpExprDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\PropertyDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\ScalarDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\Type\BuiltinType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
trait PhpAstBuilderTrait
{
    use VariableNameScoperTrait;

    private readonly BuilderFactory $builder;

    private function convertDataAccessorToPhpExpr(DataAccessorInterface $accessor): Expr
    {
        if ($accessor instanceof ScalarDataAccessor) {
            return $this->builder->val($accessor->value);
        }

        if ($accessor instanceof VariableDataAccessor) {
            return $this->builder->var($accessor->name);
        }

        if ($accessor instanceof PropertyDataAccessor) {
            return $this->builder->propertyFetch(
                $this->convertDataAccessorToPhpExpr($accessor->objectAccessor),
                $accessor->propertyName,
            );
        }

        if ($accessor instanceof FunctionDataAccessor) {
            $arguments = array_map($this->convertDataAccessorToPhpExpr(...), $accessor->arguments);

            if (null === $accessor->objectAccessor) {
                return $this->builder->funcCall($accessor->functionName, $arguments);
            }

            return $this->builder->methodCall(
                $this->convertDataAccessorToPhpExpr($accessor->objectAccessor),
                $accessor->functionName,
                $arguments,
            );
        }

        if ($accessor instanceof PhpExprDataAccessor) {
            return $accessor->php;
        }

        throw new InvalidArgumentException(sprintf('"%s" cannot be converted to PHP node.', $accessor::class));
    }

    // TODO code it better
    private function isNodeAlteringJson(EncodeDataModelNodeInterface|DecodeDataModelNodeInterface $node): bool
    {
        if ($node->isTransformed()) {
            return true;
        }

        $type = $node->getType();

        if ($node instanceof DecodeDataModelNodeInterface && $type instanceof BuiltinType && TypeIdentifier::OBJECT === $type->getTypeIdentifier()) {
            return true;
        }

        if ($node instanceof EncodeDataModelNodeInterface && $type instanceof BuiltinType && TypeIdentifier::NULL === $type->getTypeIdentifier()) {
            return true;
        }

        if ($node instanceof EncodeCollectionNode || $node instanceof DecodeCollectionNode) {
            return $this->isNodeAlteringJson($node->item);
        }

        if ($node instanceof EncodeObjectNode) {
            foreach ($node->properties as $property) {
                if ($this->isNodeAlteringJson($property)) {
                    return true;
                }
            }
        }

        if ($node instanceof DecodeObjectNode) {
            foreach ($node->properties as $property) {
                if ($this->isNodeAlteringJson($property['value'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
