<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class ForEachNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<NodeInterface> $body
     */
    public function testCompile(string $expectedSource, NodeInterface $collection, ?string $keyName, string $valueName, array $body): void
    {
        (new ForEachNode($collection, $keyName, $valueName, $body))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: NodeInterface, 2: ?string, 3: string, 4: list<NodeInterface>}>
     */
    public function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            foreach (\$foo as \$fooValue) {
            }

            PHP,
            new VariableNode('foo'),
            null,
            'fooValue',
            [],
        ];
        yield [
            <<<PHP
            foreach (\$foo as \$fooKey => \$fooValue) {
            }

            PHP,
            new VariableNode('foo'),
            'fooKey',
            'fooValue',
            [],
        ];
        yield [
            <<<PHP
            foreach (\$foo as \$fooKey => \$fooValue) {
                "foo";
                "bar";
            }

            PHP,
            new VariableNode('foo'),
            'fooKey',
            'fooValue',
            [new ExpressionNode(new ScalarNode('foo')), new ExpressionNode(new ScalarNode('bar'))],
        ];
    }
}
