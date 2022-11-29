<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast;

use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

/**
 * @internal
 */
final class Optimizer
{
    /**
     * @param list<NodeInterface> $nodes
     *
     * @return list<NodeInterface>
     */
    public function optimize(array $nodes): array
    {
        $nodes = $this->mergeStringResourceFwrites($nodes);

        // TODO optimize wherever possible
        // TODO template string?

        return $nodes;
    }

    /**
     * @param list<NodeInterface> $nodes
     *
     * @return list<NodeInterface>
     */
    private function mergeStringResourceFwrites(array $nodes): array
    {
        $stringContent = '';
        $optimizedNodes = [];

        $createFwriteExpression = fn (string $content) => new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($content)]));

        foreach ($nodes as $node) {
            if (!$this->isStringResourceFwrite($node)) {
                if ('' !== $stringContent) {
                    $optimizedNodes[] = $createFwriteExpression($stringContent);
                    $stringContent = '';
                }

                $optimizedNodes[] = $node;

                continue;
            }

            $stringContent = $stringContent.$node->node->parameters[1]->value;
        }

        if ('' !== $stringContent) {
            $optimizedNodes[] = $createFwriteExpression($stringContent);
        }

        return $optimizedNodes;
    }

    private function isStringResourceFwrite(NodeInterface $node): bool
    {
        if (!$this->isResourceFwrite($node)) {
            return false;
        }

        $dataParameter = $node->node->parameters[1] ?? null;

        return $dataParameter instanceof ScalarNode && is_string($dataParameter->value);
    }

    private function isResourceFwrite(NodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof FunctionNode || '\\fwrite' !== $currentNode->name) {
            return false;
        }

        $resourceParameter = $currentNode->parameters[0] ?? null;

        return $resourceParameter instanceof VariableNode && 'resource' === $resourceParameter->name;
    }
}
