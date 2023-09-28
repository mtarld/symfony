<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Template;

use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\CollectionNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelNodeInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ObjectNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ScalarNode;

/**
 * A base class to generate a unmarshal template PHP syntax tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract readonly class TemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function collectionNodes(CollectionNode $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function objectNodes(ObjectNode $node, array &$context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    abstract protected function scalarNodes(ScalarNode $node, array &$context): array;

    // TODO
    final public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        return [
            ...$this->providerNodes($node, $context),
            ...$this->returnDataNodes($node, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    final protected function providerNodes(DataModelNodeInterface $node, array &$context): array
    {
        if ($context['providers'][$node->identifier()] ?? false) {
            return [];
        }

        if ($node instanceof ObjectNode && $node->ghost) {
            return [];
        }

        $context['providers'][$node->identifier()] = true;

        return match (true) {
            $node instanceof CollectionNode => $this->collectionNodes($node, $context),
            $node instanceof ObjectNode => $this->objectNodes($node, $context),
            $node instanceof ScalarNode => $this->scalarNodes($node, $context),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }
}
