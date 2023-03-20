<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\Optimizer;

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
