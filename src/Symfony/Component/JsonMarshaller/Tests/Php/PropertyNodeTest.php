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
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\PropertyNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;

class PropertyNodeTest extends TestCase
{
    public function testCompile()
    {
        (new PropertyNode(new VariableNode('foo'), 'bar'))->compile($compiler = new Compiler());
        $this->assertSame('$foo->bar', $compiler->source());

        (new PropertyNode(new VariableNode('foo'), 'bar', true))->compile($compiler = new Compiler());
        $this->assertSame('$foo::$bar', $compiler->source());
    }
}
