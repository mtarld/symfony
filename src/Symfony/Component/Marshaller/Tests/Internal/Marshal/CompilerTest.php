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
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;

class CompilerTest extends TestCase
{
    public function testRaw()
    {
        $this->assertSame('rawString', (new Compiler())->raw('rawString')->source());
    }

    public function testLine()
    {
        $compiler = new Compiler();

        $this->assertSame('lineString'.\PHP_EOL, $compiler->line('lineString')->source());
        $this->assertSame('lineString'.\PHP_EOL.'    lineString'.\PHP_EOL, $compiler->indent()->line('lineString')->source());
        $this->assertSame('lineString'.\PHP_EOL.'    lineString'.\PHP_EOL.'lineString'.\PHP_EOL, $compiler->outdent()->line('lineString')->source());
    }

    public function testCompile()
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();

        $this->assertSame('"foo";'.\PHP_EOL.'    "bar";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testReset()
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());

        $compiler->indent();
        $compiler->reset();

        $this->assertSame('"bar";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('bar')))->source());
    }

    public function testSubcompile()
    {
        $compiler = new Compiler();

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->compile(new ExpressionNode(new ScalarNode('foo')))->source());
        $this->assertSame('"bar";'.\PHP_EOL, $compiler->subcompile(new ExpressionNode(new ScalarNode('bar'))));

        $this->assertSame('"foo";'.\PHP_EOL, $compiler->source());
    }
}
