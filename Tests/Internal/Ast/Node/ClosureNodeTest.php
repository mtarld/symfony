<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\ArgumentsNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ClosureNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;

final class ClosureNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<NodeInterface> $body
     */
    public function testCompile(string $expectedSource, ArgumentsNode $arguments, ?string $returnType, bool $static, array $body): void
    {
        (new ClosureNode($arguments, $returnType, $static, $body))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: ArgumentsNode, 2: ?string, 3: bool, 4: list<NodeInterface>}>
     */
    public function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            function (string \$foo) {
            }
            PHP,
            new ArgumentsNode(['foo' => 'string']),
            null,
            false,
            [],
        ];
        yield [
            <<<PHP
            static function (): string {
            }
            PHP,
            new ArgumentsNode([]),
            'string',
            true,
            [],
        ];
        yield [
            <<<PHP
            static function (): void {
                "foo";
                "bar";
            }
            PHP,
            new ArgumentsNode([]),
            'void',
            true,
            [new ExpressionNode(new ScalarNode('foo')), new ExpressionNode(new ScalarNode('bar'))],
        ];
    }
}
