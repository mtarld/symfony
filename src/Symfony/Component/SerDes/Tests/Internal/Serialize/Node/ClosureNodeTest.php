<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArgumentsNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ClosureNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;

class ClosureNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<NodeInterface> $body
     */
    public function testCompile(string $expectedSource, ArgumentsNode $arguments, ?string $returnType, bool $static, array $body)
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
