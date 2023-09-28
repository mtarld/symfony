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
use Symfony\Component\JsonMarshaller\Php\NewNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;

class NewNodeTest extends TestCase
{
    public function testCompile()
    {
        (new NewNode('Foo', new ArgumentsNode([new VariableNode('bar')])))->compile($compiler = new Compiler());
        $this->assertSame('new Foo($bar)', $compiler->source());
    }
}
