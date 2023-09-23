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
use Symfony\Component\Json\Php\AssignNode;
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ParametersNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\ScalarNode;
use Symfony\Component\Json\Php\TryCatchNode;
use Symfony\Component\Json\Php\VariableNode;

class TryCatchNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     *
     * @param list<PhpNodeInterface> $tryNodes
     * @param list<PhpNodeInterface> $catchNodes
     */
    public function testCompile(string $expectedSource, array $tryNodes, array $catchNodes, ParametersNode $catchParameters)
    {
        (new TryCatchNode($tryNodes, $catchNodes, $catchParameters))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<array{0: string, 1: list<PhpNodeInterface>, 2: list<PhpNodeInterface>, 3: ParametersNode}>
     */
    public static function compileDataProvider(): iterable
    {
        yield [
            <<<PHP
            try {
            } catch (\$e) {
            }

            PHP,
            [],
            [],
            new ParametersNode(['e' => null]),
        ];
        yield [
            <<<PHP
            try {
                \$foo = true;
            } catch (Exception \$e) {
                \$foo = false;
            }

            PHP,
            [new ExpressionNode(new AssignNode(new VariableNode('foo'), new ScalarNode(true)))],
            [new ExpressionNode(new AssignNode(new VariableNode('foo'), new ScalarNode(false)))],
            new ParametersNode(['e' => 'Exception']),
        ];
    }
}
