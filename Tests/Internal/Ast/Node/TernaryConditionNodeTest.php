<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\TernaryConditionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

final class TernaryConditionNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new TernaryConditionNode(new VariableNode('foo'), new VariableNode('trueFoo'), new VariableNode('falseFoo')))->compile($compiler = new Compiler());
        $this->assertSame('$foo ? $trueFoo : $falseFoo', $compiler->source());
    }
}
