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
use Symfony\Component\Json\Php\TernaryConditionNode;
use Symfony\Component\Json\Php\VariableNode;

class TernaryConditionNodeTest extends TestCase
{
    public function testCompile()
    {
        (new TernaryConditionNode(new VariableNode('foo'), new VariableNode('trueFoo'), new VariableNode('falseFoo')))->compile($compiler = new Compiler());
        $this->assertSame('($foo ? $trueFoo : $falseFoo)', $compiler->source());
    }
}
