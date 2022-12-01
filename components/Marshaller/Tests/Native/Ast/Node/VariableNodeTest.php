<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class VariableNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new VariableNode('foo'))->compile($compiler = new Compiler());
        $this->assertSame('$foo', $compiler->source());
    }
}
