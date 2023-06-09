<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Serialize;

use Symfony\Component\Serializer\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\VariableNode;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Optimizer
{
    /**
     * @template T of NodeInterface|list<NodeInterface>
     *
     * @param T $subject
     *
     * @return T
     */
    public function optimize(NodeInterface|array $subject): NodeInterface|array
    {
        /** @var T $optimized */
        $optimized = $subject instanceof NodeInterface ? $subject->optimize($this) : $this->optimizeNodeCollection($subject);

        return $optimized;
    }

    /**
     * @param list<NodeInterface> $nodes
     *
     * @return list<NodeInterface>
     */
    private function optimizeNodeCollection(array $nodes): array
    {
        return $this->mergeResourceStringFwrites($nodes);
    }

    /**
     * @param list<NodeInterface> $nodes
     *
     * @return list<NodeInterface>
     */
    private function mergeResourceStringFwrites(array $nodes): array
    {
        if (!array_is_list($nodes)) {
            return $nodes;
        }

        $createFwriteExpression = fn (string $content) => new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($content)]));

        $stringContent = '';
        $mergedNodes = [];

        foreach ($nodes as $node) {
            if (!$this->isStringResourceFwrite($node)) {
                if ('' !== $stringContent) {
                    $mergedNodes[] = $createFwriteExpression($stringContent);
                    $stringContent = '';
                }

                $mergedNodes[] = $node;

                continue;
            }

            /**
             * @var ExpressionNode<FunctionNode> $node
             * @var ScalarNode                   $stringArgument
             */
            $stringArgument = $node->node->arguments[1];

            $stringContent = $stringContent.$stringArgument->value;
        }

        if ('' !== $stringContent) {
            $mergedNodes[] = $createFwriteExpression($stringContent);
        }

        /** @var list<NodeInterface> $optimizedNodes */
        $optimizedNodes = array_map($this->optimize(...), $mergedNodes);

        return $optimizedNodes;
    }

    private function isStringResourceFwrite(NodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof FunctionNode || '\\fwrite' !== $currentNode->name) {
            return false;
        }

        $resourceArgument = $currentNode->arguments[0] ?? null;
        $dataArgument = $currentNode->arguments[1] ?? null;

        return $resourceArgument instanceof VariableNode && 'resource' === $resourceArgument->name
            && $dataArgument instanceof ScalarNode && \is_string($dataArgument->value);
    }
}
