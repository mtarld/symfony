<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class ExpressionNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new ExpressionNode(new VariableNode('foo')))->compile($compiler = new Compiler());
        $this->assertSame('$foo;'.PHP_EOL, $compiler->source());
    }
}
