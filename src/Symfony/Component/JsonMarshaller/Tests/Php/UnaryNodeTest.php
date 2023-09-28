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
use Symfony\Component\JsonMarshaller\Exception\InvalidArgumentException;
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\UnaryNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;

class UnaryNodeTest extends TestCase
{
    public function testCompile()
    {
        (new UnaryNode('!', new VariableNode('foo')))->compile($compiler = new Compiler());
        $this->assertSame('!$foo', $compiler->source());
    }

    public function testThrowOnInvalidOperator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "invalid" operator.');

        new UnaryNode('invalid', new VariableNode('foo'));
    }
}
