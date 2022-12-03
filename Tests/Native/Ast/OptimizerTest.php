<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

final class OptimizerTest extends TestCase
{
    /**
     * @dataProvider mergeStringFwritesDataProvider
     *
     * @param list<NodeInterface> $expectedNodes
     * @param list<NodeInterface> $nodes
     */
    public function testMergeStringFwrites(array $expectedNodes, array $nodes): void
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<NodeInterface>, 1: list<NodeInterface>}>
     */
    public function mergeStringFwritesDataProvider(): iterable
    {
        $createFwriteExpression = fn (NodeInterface $content) => new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $content]));

        yield [[
            $createFwriteExpression(new ScalarNode('foobar')),
        ], [
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new VariableNode('bar')),
            $createFwriteExpression(new ScalarNode('baz')),
        ], [
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new VariableNode('bar')),
            $createFwriteExpression(new ScalarNode('baz')),
        ]];

        yield [[
            new ExpressionNode(new FunctionNode('fooFunction', [])),
            new ExpressionNode(new FunctionNode('barFunction', [])),
            $createFwriteExpression(new ScalarNode('baz')),
        ], [
            new ExpressionNode(new FunctionNode('fooFunction', [])),
            new ExpressionNode(new FunctionNode('barFunction', [])),
            $createFwriteExpression(new ScalarNode('baz')),
        ]];
    }
}
