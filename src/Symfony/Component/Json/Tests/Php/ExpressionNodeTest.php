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
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\VariableNode;

class ExpressionNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ExpressionNode(new VariableNode('foo')))->compile($compiler = new Compiler());
        $this->assertSame('$foo;'."\n", $compiler->source());
    }
}
