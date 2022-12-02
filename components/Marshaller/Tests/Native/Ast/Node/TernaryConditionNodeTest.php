<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\TernaryConditionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class TernaryConditionNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new TernaryConditionNode(new VariableNode('foo'), new VariableNode('trueFoo'), new VariableNode('falseFoo')))->compile($compiler = new Compiler());
        $this->assertSame('$foo ? $trueFoo : $falseFoo', $compiler->source());
    }
}
