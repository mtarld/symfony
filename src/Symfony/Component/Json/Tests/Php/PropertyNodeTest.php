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
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\PropertyNode;
use Symfony\Component\Json\Php\VariableNode;

class PropertyNodeTest extends TestCase
{
    public function testCompile()
    {
        (new PropertyNode(new VariableNode('foo'), 'bar'))->compile($compiler = new Compiler());
        $this->assertSame('$foo->bar', $compiler->source());

        (new PropertyNode(new VariableNode('foo'), 'bar', nullSafe: true))->compile($compiler = new Compiler());
        $this->assertSame('$foo?->bar', $compiler->source());

        (new PropertyNode(new VariableNode('foo'), 'bar', static: true))->compile($compiler = new Compiler());
        $this->assertSame('$foo::$bar', $compiler->source());
    }
}
