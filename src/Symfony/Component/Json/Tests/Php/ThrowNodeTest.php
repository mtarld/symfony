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
use Symfony\Component\Json\Php\ThrowNode;
use Symfony\Component\Json\Php\VariableNode;

class ThrowNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ThrowNode(new VariableNode('foo')))->compile($compiler = new Compiler());
        $this->assertSame('throw $foo', $compiler->source());
    }
}
