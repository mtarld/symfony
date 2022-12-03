<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\UnaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

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
