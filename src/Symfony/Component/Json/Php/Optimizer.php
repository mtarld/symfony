<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Php;

/**
 * Optimizes a PHP syntax tree.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
// TODO do better?
final class Optimizer
{
    /**
     * @template T of PhpNodeInterface|list<PhpNodeInterface>
     *
     * @param T $subject
     *
     * @return T
     */
    public function optimize(PhpNodeInterface|array $subject): PhpNodeInterface|array
    {
        /** @var T $optimized */
        $optimized = $subject instanceof PhpNodeInterface ? $subject->optimize($this) : $this->optimizeNodeCollection($subject);

        return $optimized;
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function optimizeNodeCollection(array $nodes): array
    {
        $nodes = $this->mergeYieldStrings($nodes);
        $nodes = $this->mergeStreamWriteStrings($nodes);
        $nodes = $this->mergeFwriteStrings($nodes);

        return $nodes;
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function mergeYieldStrings(array $nodes): array
    {
        if (!array_is_list($nodes)) {
            return $nodes;
        }

        $createYieldExpression = fn (string $content) => new ExpressionNode(new YieldNode(new ScalarNode($content)));

        $stringContent = '';
        $mergedNodes = [];

        foreach ($nodes as $node) {
            if (!$this->isStringYield($node)) {
                if ('' !== $stringContent) {
                    $mergedNodes[] = $createYieldExpression($stringContent);
                    $stringContent = '';
                }

                $mergedNodes[] = $node;

                continue;
            }

            /**
             * @var ExpressionNode<YieldNode> $node
             * @var ScalarNode                $stringArgument
             */
            $stringArgument = $node->node->value;
            $stringContent .= $stringArgument->value;
        }

        if ('' !== $stringContent) {
            $mergedNodes[] = $createYieldExpression($stringContent);
        }

        /** @var list<PhpNodeInterface> $optimizedNodes */
        $optimizedNodes = array_map($this->optimize(...), $mergedNodes);

        return $optimizedNodes;
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function mergeStreamWriteStrings(array $nodes): array
    {
        if (!array_is_list($nodes)) {
            return $nodes;
        }

        $createStreamWriteExpression = fn (string $content) => new ExpressionNode(new MethodCallNode(
            new VariableNode('stream'),
            'write',
            new ArgumentsNode([new ScalarNode($content)]),
        ));

        $stringContent = '';
        $mergedNodes = [];

        foreach ($nodes as $node) {
            if (!$this->isStringStreamWrite($node)) {
                if ('' !== $stringContent) {
                    $mergedNodes[] = $createStreamWriteExpression($stringContent);
                    $stringContent = '';
                }

                $mergedNodes[] = $node;

                continue;
            }

            /**
             * @var ExpressionNode<MethodCallNode> $node
             * @var ScalarNode                     $stringArgument
             */
            $stringArgument = $node->node->arguments->arguments[0];
            $stringContent .= $stringArgument->value;
        }

        if ('' !== $stringContent) {
            $mergedNodes[] = $createStreamWriteExpression($stringContent);
        }

        /** @var list<PhpNodeInterface> $optimizedNodes */
        $optimizedNodes = array_map($this->optimize(...), $mergedNodes);

        return $optimizedNodes;
    }

    /**
     * @param list<PhpNodeInterface> $nodes
     *
     * @return list<PhpNodeInterface>
     */
    private function mergeFwriteStrings(array $nodes): array
    {
        if (!array_is_list($nodes)) {
            return $nodes;
        }

        $createFwriteExpression = fn (string $content) => new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([
            new VariableNode('stream'),
            new ScalarNode($content),
        ])));

        $stringContent = '';
        $mergedNodes = [];

        foreach ($nodes as $node) {
            if (!$this->isStringFwrite($node)) {
                if ('' !== $stringContent) {
                    $mergedNodes[] = $createFwriteExpression($stringContent);
                    $stringContent = '';
                }

                $mergedNodes[] = $node;

                continue;
            }

            /**
             * @var ExpressionNode<FunctionCallNode> $node
             * @var ScalarNode                       $stringArgument
             */
            $stringArgument = $node->node->arguments->arguments[1];
            $stringContent .= $stringArgument->value;
        }

        if ('' !== $stringContent) {
            $mergedNodes[] = $createFwriteExpression($stringContent);
        }

        /** @var list<PhpNodeInterface> $optimizedNodes */
        $optimizedNodes = array_map($this->optimize(...), $mergedNodes);

        return $optimizedNodes;
    }

    private function isStringYield(PhpNodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof YieldNode) {
            return false;
        }

        return $currentNode->value instanceof ScalarNode && \is_string($currentNode->value->value);
    }

    private function isStringStreamWrite(PhpNodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof MethodCallNode || 'write' !== $currentNode->method) {
            return false;
        }

        $dataArgument = $currentNode->arguments->arguments[0] ?? null;

        return $dataArgument instanceof ScalarNode && \is_string($dataArgument->value);
    }

    private function isStringFwrite(PhpNodeInterface $node): bool
    {
        if (!$node instanceof ExpressionNode) {
            return false;
        }

        $currentNode = $node->node;

        if (!$currentNode instanceof FunctionCallNode || '\\fwrite' !== $currentNode->name) {
            return false;
        }

        $resourceArgument = $currentNode->arguments->arguments[0] ?? null;
        $dataArgument = $currentNode->arguments->arguments[1] ?? null;

        return $resourceArgument instanceof VariableNode && 'stream' === $resourceArgument->name
            && $dataArgument instanceof ScalarNode && \is_string($dataArgument->value);
    }
}
