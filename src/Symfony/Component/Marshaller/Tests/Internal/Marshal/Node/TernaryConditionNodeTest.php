<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\TernaryConditionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;

class TernaryConditionNodeTest extends TestCase
{
    public function testCompile()
    {
        (new TernaryConditionNode(new VariableNode('foo'), new VariableNode('trueFoo'), new VariableNode('falseFoo')))->compile($compiler = new Compiler());
        $this->assertSame('$foo ? $trueFoo : $falseFoo', $compiler->source());
    }
}
