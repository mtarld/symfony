<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\Optimizer;

class OptimizerTest extends TestCase
{
    /**
     * @dataProvider mergeStringFwritesDataProvider
     *
     * @param list<NodeInterface> $expectedNodes
     * @param list<NodeInterface> $nodes
     */
    public function testMergeStringFwrites(array $expectedNodes, array $nodes)
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<NodeInterface>, 1: list<NodeInterface>}>
     */
    public static function mergeStringFwritesDataProvider(): iterable
    {
        $createFwriteExpression = fn (NodeInterface $content) => new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $content]));

        yield [[
            $createFwriteExpression(new ScalarNode('foobar')),
        ], [
            $createFwriteExpression(new ScalarNode('foo')),
            $createFwriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            'foo' => $createFwriteExpression(new ScalarNode('foo')),
            'bar' => $createFwriteExpression(new ScalarNode('bar')),
        ], [
            'foo' => $createFwriteExpression(new ScalarNode('foo')),
            'bar' => $createFwriteExpression(new ScalarNode('bar')),
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
