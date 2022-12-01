<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class IfNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<NodeInterface>                                              $onIf
     * @param list<NodeInterface>                                              $onElse
     * @param list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfs
     */
    public function testCompile(string $expectedSource, NodeInterface $condition, array $onIf, array $onElse, array $elseIfs): void
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
