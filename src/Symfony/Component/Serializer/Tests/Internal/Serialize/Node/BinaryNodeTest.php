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
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Internal\Serialize\Compiler;
use Symfony\Component\Serializer\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\VariableNode;

class BinaryNodeTest extends TestCase
{
    public function testCompile()
    {
        (new BinaryNode('&&', new VariableNode('foo'), new VariableNode('bar')))->compile($compiler = new Compiler());
        $this->assertSame('$foo && $bar', $compiler->source());
    }

    public function testThrowOnInvalidOperator()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "invalid" operator.');

        new BinaryNode('invalid', new VariableNode('foo'), new VariableNode('bar'));
    }
}
