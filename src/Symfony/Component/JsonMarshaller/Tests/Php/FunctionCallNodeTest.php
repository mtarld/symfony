<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Php;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;

class FunctionCallNodeTest extends TestCase
{
    public function testCompile()
    {
        (new FunctionCallNode('fooFunction', new ArgumentsNode([new VariableNode('foo'), new ScalarNode(true)])))->compile($compiler = new Compiler());
        $this->assertSame('fooFunction($foo, true)', $compiler->source());

        (new FunctionCallNode(new VariableNode('fooFunction'), new ArgumentsNode([])))->compile($compiler = new Compiler());
        $this->assertSame('($fooFunction)()', $compiler->source());
    }
}
