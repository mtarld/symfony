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
use Symfony\Component\JsonMarshaller\Php\VariableNode;

class VariableNodeTest extends TestCase
{
    public function testCompile()
    {
        (new VariableNode('foo'))->compile($compiler = new Compiler());
        $this->assertSame('$foo', $compiler->source());

        (new VariableNode('foo', byReference: true))->compile($compiler = new Compiler());
        $this->assertSame('&$foo', $compiler->source());
    }
}
