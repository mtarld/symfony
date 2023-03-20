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
use Symfony\Component\Json\Php\ScalarNode;
use Symfony\Component\Json\Php\VariableNode;

class ArgumentsNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ArgumentsNode([new VariableNode('foo')]))->compile($compiler = new Compiler());
        $this->assertSame('$foo', $compiler->source());

        (new ArgumentsNode([new ScalarNode(123), new VariableNode('bar', byReference: true)]))->compile($compiler = new Compiler());
        $this->assertSame('123, &$bar', $compiler->source());
    }
}
