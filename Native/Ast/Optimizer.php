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

            /** @var ExpressionNode<FunctionNode<array{0: VariableNode, 1: ScalarNode}>> $node */
            $stringContent = $stringContent.$node->node->parameters[1]->value;
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

        $resourceParameter = $currentNode->parameters[0] ?? null;
        $dataParameter = $node->node->parameters[1] ?? null;

        return $resourceParameter instanceof VariableNode && 'resource' === $resourceParameter->name
            && $dataParameter instanceof ScalarNode && is_string($dataParameter->value);
    }
}
