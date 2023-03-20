<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\IfNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;

class IfNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<NodeInterface>                                              $onIf
     * @param list<NodeInterface>                                              $onElse
     * @param list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfs
     */
    public function testCompile(string $expectedSource, NodeInterface $condition, array $onIf, array $onElse, array $elseIfs)
    {
        (new IfNode($condition, $onIf, $onElse, $elseIfs))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: NodeInterface, 2: list<NodeInterface>, 3: list<NodeInterface>, 4: list<array{condition: NodeInterface, body: list<NodeInterface>}>}>
     */
    public function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            if (\$foo) {
                "onIf";
            }

            PHP,
            new VariableNode('foo'),
            [new ExpressionNode(new ScalarNode('onIf'))],
            [],
            [],
        ];
        yield [
            <<<PHP
            if (\$foo) {
                "onIf";
            } else {
                "onElse";
            }

            PHP,
            new VariableNode('foo'),
            [new ExpressionNode(new ScalarNode('onIf'))],
            [new ExpressionNode(new ScalarNode('onElse'))],
            [],
        ];
        yield [
            <<<PHP
            if (\$foo) {
            } elseif (\$elseIfOne) {
                "onElseIfOne";
            } elseif (\$elseIfTwo) {
                "onElseIfTwo";
            }

            PHP,
            new VariableNode('foo'),
            [],
            [],
            [
                ['condition' => new VariableNode('elseIfOne'), 'body' => [new ExpressionNode(new ScalarNode('onElseIfOne'))]],
                ['condition' => new VariableNode('elseIfTwo'), 'body' => [new ExpressionNode(new ScalarNode('onElseIfTwo'))]],
            ],
        ];
    }
}
