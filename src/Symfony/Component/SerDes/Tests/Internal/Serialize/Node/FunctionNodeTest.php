<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;

class FunctionNodeTest extends TestCase
{
    public function testCompile()
    {
        (new FunctionNode('fooFunction', [new VariableNode('foo'), new ScalarNode(true)]))->compile($compiler = new Compiler());
        $this->assertSame('fooFunction($foo, true)', $compiler->source());
    }
}
