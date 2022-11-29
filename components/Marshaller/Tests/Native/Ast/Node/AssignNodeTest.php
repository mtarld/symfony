<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class AssignNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new AssignNode(new VariableNode('foo'), new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('$foo = true', $compiler->source());
    }
}
