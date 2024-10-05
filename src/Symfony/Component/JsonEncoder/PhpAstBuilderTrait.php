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
use Symfony\Component\JsonEncoder\DataModel\FunctionDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\PhpExprDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\PropertyDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\ScalarDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
trait PhpAstBuilderTrait
{
    use VariableNameScoperTrait;

    private BuilderFactory $builder;

    private function convertDataAccessorToPhpExpr(DataAccessorInterface $accessor): Expr
    {
        if ($accessor instanceof ScalarDataAccessor) {
            return $this->builder->val($accessor->getValue());
        }

        if ($accessor instanceof VariableDataAccessor) {
            return $this->builder->var($accessor->getName());
        }

        if ($accessor instanceof PropertyDataAccessor) {
            return $this->builder->propertyFetch(
                $this->convertDataAccessorToPhpExpr($accessor->getObjectAccessor()),
                $accessor->getPropertyName(),
            );
        }

        if ($accessor instanceof FunctionDataAccessor) {
            $arguments = array_map($this->convertDataAccessorToPhpExpr(...), $accessor->getArguments());

            if (null === $accessor->getObjectAccessor()) {
                return $this->builder->funcCall($accessor->getFunctionName(), $arguments);
            }

            return $this->builder->methodCall(
                $this->convertDataAccessorToPhpExpr($accessor->getObjectAccessor()),
                $accessor->getFunctionName(),
                $arguments,
            );
        }

        if ($accessor instanceof PhpExprDataAccessor) {
            return $accessor->getPhp();
        }

        throw new InvalidArgumentException(\sprintf('"%s" cannot be converted to PHP node.', $accessor::class));
    }
}
