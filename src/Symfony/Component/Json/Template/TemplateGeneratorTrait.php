<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template;

use Symfony\Component\Encoder\DataModel\DataAccessorInterface;
use Symfony\Component\Encoder\DataModel\Decode\CollectionNode as DecodeCollectionNode;
use Symfony\Component\Encoder\DataModel\Decode\DataModelNodeInterface as DecodeDataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Decode\ObjectNode as DecodeObjectNode;
use Symfony\Component\Encoder\DataModel\Encode\CollectionNode as EncodeCollectionNode;
use Symfony\Component\Encoder\DataModel\Encode\DataModelNodeInterface as EncodeDataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Encode\ObjectNode as EncodeObjectNode;
use Symfony\Component\Encoder\DataModel\FunctionDataAccessor;
use Symfony\Component\Encoder\DataModel\PropertyDataAccessor;
use Symfony\Component\Encoder\DataModel\ScalarDataAccessor;
use Symfony\Component\Encoder\DataModel\VariableDataAccessor;
use Symfony\Component\Encoder\Exception\InvalidArgumentException;
use Symfony\Component\Json\Php\ArgumentsNode;
use Symfony\Component\Json\Php\FunctionCallNode;
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\PropertyNode;
use Symfony\Component\Json\Php\ScalarNode;
use Symfony\Component\Json\Php\VariableNode;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @internal
 */
trait TemplateGeneratorTrait
{
    private function convertDataAccessorToPhpNode(DataAccessorInterface $accessor): PhpNodeInterface
    {
        if ($accessor instanceof ScalarDataAccessor) {
            return new ScalarNode($accessor->value);
        }

        if ($accessor instanceof VariableDataAccessor) {
            return new VariableNode($accessor->name);
        }

        if ($accessor instanceof PropertyDataAccessor) {
            return new PropertyNode(
                $this->convertDataAccessorToPhpNode($accessor->objectAccessor),
                $accessor->propertyName,
            );
        }

        if ($accessor instanceof FunctionDataAccessor) {
            $arguments = new ArgumentsNode(array_map($this->convertDataAccessorToPhpNode(...), $accessor->arguments));

            if (null === $accessor->objectAccessor) {
                return new FunctionCallNode($accessor->functionName, $arguments);
            }

            return new MethodCallNode($this->convertDataAccessorToPhpNode($accessor->objectAccessor), $accessor->functionName, $arguments);
        }

        if ($accessor instanceof PhpNodeDataAccessor) {
            return $accessor->php;
        }

        throw new InvalidArgumentException(sprintf('"%s" cannot be converted to PHP node.', $accessor::class));
    }

    private function isNodeAlteringJson(EncodeDataModelNodeInterface|DecodeDataModelNodeInterface $node): bool
    {
        if ($node->isTransformed()) {
            return true;
        }

        if ($node instanceof DecodeDataModelNodeInterface && $node->getType()->isObject()) {
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
