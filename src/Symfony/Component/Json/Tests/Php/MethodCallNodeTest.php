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
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\ScalarNode;
use Symfony\Component\Json\Php\VariableNode;

class MethodCallNodeTest extends TestCase
{
    public function testCompile()
    {
        (new MethodCallNode(new VariableNode('object'), 'method', new ArgumentsNode([new VariableNode('foo'), new ScalarNode(true)])))->compile($compiler = new Compiler());
        $this->assertSame('$object->method($foo, true)', $compiler->source());

        (new MethodCallNode(new VariableNode('object'), 'method', new ArgumentsNode([]), true))->compile($compiler = new Compiler());
        $this->assertSame('$object::method()', $compiler->source());
    }
}
