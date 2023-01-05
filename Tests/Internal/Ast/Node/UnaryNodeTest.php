<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\UnaryNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

final class UnaryNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new UnaryNode('!', new VariableNode('foo')))->compile($compiler = new Compiler());
        $this->assertSame('!$foo', $compiler->source());
    }

    public function testThrowOnInvalidOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "invalid" operator.');

        new UnaryNode('invalid', new VariableNode('foo'));
    }
}
