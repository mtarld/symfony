<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ArrayAccessNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class ArrayAccessNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new ArrayAccessNode(new VariableNode('foo'), new ScalarNode('bar')))->compile($compiler = new Compiler());
        $this->assertSame('$foo["bar"]', $compiler->source());
    }
}
