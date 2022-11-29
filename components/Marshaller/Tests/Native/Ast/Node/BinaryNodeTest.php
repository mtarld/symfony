<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class BinaryNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new BinaryNode('&&', new VariableNode('foo'), new VariableNode('bar')))->compile($compiler = new Compiler());
        $this->assertSame('($foo) && ($bar)', $compiler->source());
    }

    public function testThrowOnInvalidOperator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "invalid" operator.');

        new BinaryNode('invalid', new VariableNode('foo'), new VariableNode('bar'));
    }
}
