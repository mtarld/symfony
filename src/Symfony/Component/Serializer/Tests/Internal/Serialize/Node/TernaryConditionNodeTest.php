<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Internal\Serialize\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Internal\Serialize\Compiler;
use Symfony\Component\Serializer\Internal\Serialize\Node\TernaryConditionNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\VariableNode;

class TernaryConditionNodeTest extends TestCase
{
    public function testCompile()
    {
        (new TernaryConditionNode(new VariableNode('foo'), new VariableNode('trueFoo'), new VariableNode('falseFoo')))->compile($compiler = new Compiler());
        $this->assertSame('($foo ? $trueFoo : $falseFoo)', $compiler->source());
    }
}
