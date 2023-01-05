<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Ast;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;

final class CompilerTest extends TestCase
{
    public function testRaw(): void
    {
        $this->assertSame('rawString', (new Compiler())->raw('rawString')->source());
    }

    public function testLine(): void
    {
        $compiler = new Compiler();

        $this->assertSame('lineString'.\PHP_EOL, $compiler->line('lineString')->source());
        $this->assertSame('lineString'.\PHP_EOL.'    lineString'.\PHP_EOL, $compiler->indent()->line('lineString')->source());
        $this->assertSame('lineString'.\PHP_EOL.'    lineString'.\PHP_EOL.'lineString'.\PHP_EOL, $compiler->outdent()->line('lineString')->source());
    }

    public function testCompile(): void
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();

        $this->assertSame('"foo";'.\PHP_EOL.'    "bar";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testReset(): void
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();
        $compiler->reset();

        $this->assertSame('"bar";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testSubcompile(): void
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());
        $this->assertSame('"bar";'.\PHP_EOL, $compiler->subcompile(new ExpressionNode(new ScalarNode('bar'))));

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->source());
    }
}
