<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests\Php;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Json\Php\ArgumentsNode;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\FunctionCallNode;
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\Optimizer;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\ScalarNode;
use Symfony\Component\Json\Php\VariableNode;
use Symfony\Component\Json\Php\YieldNode;

class OptimizerTest extends TestCase
{
    /**
     * @dataProvider mergeStringYieldsDataProvider
     *
     * @param list<PhpNodeInterface> $expectedNodes
     * @param list<PhpNodeInterface> $nodes
     */
    public function testMergeStringYields(array $expectedNodes, array $nodes)
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<PhpNodeInterface>, 1: list<PhpNodeInterface>}>
     */
    public static function mergeStringYieldsDataProvider(): iterable
    {
        $createYieldExpression = fn (PhpNodeInterface $content) => new ExpressionNode(new YieldNode($content));

        yield [[
            $createYieldExpression(new ScalarNode('foobar')),
        ], [
            $createYieldExpression(new ScalarNode('foo')),
            $createYieldExpression(new ScalarNode('bar')),
        ]];

        yield [[
            'foo' => $createYieldExpression(new ScalarNode('foo')),
            'bar' => $createYieldExpression(new ScalarNode('bar')),
        ], [
            'foo' => $createYieldExpression(new ScalarNode('foo')),
            'bar' => $createYieldExpression(new ScalarNode('bar')),
        ]];

        yield [[
            $createYieldExpression(new ScalarNode('foo')),
            $createYieldExpression(new VariableNode('bar')),
            $createYieldExpression(new ScalarNode('baz')),
        ], [
            $createYieldExpression(new ScalarNode('foo')),
            $createYieldExpression(new VariableNode('bar')),
            $createYieldExpression(new ScalarNode('baz')),
        ]];
    }

    /**
     * @dataProvider mergeStringStreamWritesDataProvider
     *
     * @param list<PhpNodeInterface> $expectedNodes
     * @param list<PhpNodeInterface> $nodes
     */
    public function testMergeStringStreamWrites(array $expectedNodes, array $nodes)
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<PhpNodeInterface>, 1: list<PhpNodeInterface>}>
     */
    public static function mergeStringStreamWritesDataProvider(): iterable
    {
        $createStreamWriteExpression = fn (PhpNodeInterface $content) => new ExpressionNode(new MethodCallNode(
            new VariableNode('stream'),
            'write',
            new ArgumentsNode([$content]),
        ));

        yield [[
            $createStreamWriteExpression(new ScalarNode('foobar')),
        ], [
            $createStreamWriteExpression(new ScalarNode('foo')),
            $createStreamWriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            'foo' => $createStreamWriteExpression(new ScalarNode('foo')),
            'bar' => $createStreamWriteExpression(new ScalarNode('bar')),
        ], [
            'foo' => $createStreamWriteExpression(new ScalarNode('foo')),
            'bar' => $createStreamWriteExpression(new ScalarNode('bar')),
        ]];

        yield [[
            $createStreamWriteExpression(new ScalarNode('foo')),
            $createStreamWriteExpression(new VariableNode('bar')),
            $createStreamWriteExpression(new ScalarNode('baz')),
        ], [
            $createStreamWriteExpression(new ScalarNode('foo')),
            $createStreamWriteExpression(new VariableNode('bar')),
            $createStreamWriteExpression(new ScalarNode('baz')),
        ]];
    }

    /**
     * @dataProvider mergeStringFwritesDataProvider
     *
     * @param list<PhpNodeInterface> $expectedNodes
     * @param list<PhpNodeInterface> $nodes
     */
    public function testMergeStringFwrites(array $expectedNodes, array $nodes)
    {
        $this->assertEquals($expectedNodes, (new Optimizer())->optimize($nodes));
    }

    /**
     * @return iterable<array{0: list<PhpNodeInterface>, 1: list<PhpNodeInterface>}>
     */
    public static function mergeStringFwritesDataProvider(): iterable
    {
        $createFwriteExpression = fn (PhpNodeInterface $content) => new ExpressionNode(new FunctionCallNode(
            '\fwrite',
            new ArgumentsNode([new VariableNode('stream'), $content]),
        ));

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
    }
}
